<?php

namespace App\Hosting\Kubernetes;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use InvalidArgumentException;
use RuntimeException;

final readonly class KubernetesProvisioner
{
    public function __construct(private Factory $http, private TenantWorkloadPlan $plans, private DataClusterPlan $dataPlans) {}

    /**
     * @param  array<string, mixed>  $settings
     * @param  array{url: string, token: string, ca_certificate?: string}  $connection
     * @return array{applied: bool, ready: bool, dry_run: bool, namespace: string, resources: list<array{kind: string, name: string, status: string}>}
     */
    public function deploy(string $teamId, string $workloadId, string $image, array $settings, array $connection, bool $dryRun = false): array
    {
        $plan = $this->plans->build($teamId, $workloadId, $image, $settings);
        if (count($plan) < 7 || count($plan) > 16 || ($plan[0]['body']['kind'] ?? '') !== 'Namespace') {
            throw new InvalidArgumentException('Use an intact operator-generated tenant workload plan.');
        }
        $client = $this->client($connection);
        $namespace = $plan[0]['body']['metadata']['name'];
        $deployment = array_values(array_filter($plan, fn (array $resource): bool => $resource['body']['kind'] === 'Deployment'))[0];
        $runtime = $deployment['body']['spec']['template']['spec']['runtimeClassName'];
        $response = $client->get('/apis/node.k8s.io/v1/runtimeclasses/'.rawurlencode($runtime));
        if (! $response->successful() || $response->json('metadata.name') !== $runtime
            || ! in_array($response->json('handler'), ['runsc', 'runsc-kvm', 'kata', 'kata-qemu', 'kata-clh', 'kata-runtime'], true)) {
            throw new RuntimeException('The configured sandbox RuntimeClass is unavailable. No workload was applied.');
        }
        if (isset($settings['ingress'])) {
            $issuer = $client->get('/apis/cert-manager.io/v1/clusterissuers/'.rawurlencode($settings['ingress']['issuer']));
            $class = $client->get('/apis/networking.k8s.io/v1/ingressclasses/'.rawurlencode($settings['ingress']['class']));
            if (! $issuer->successful() || ! $class->successful()
                || $issuer->json('metadata.name') !== $settings['ingress']['issuer']
                || $class->json('metadata.name') !== $settings['ingress']['class']
                || ! collect($issuer->json('status.conditions') ?? [])->contains(fn (array $condition): bool => ($condition['type'] ?? '') === 'Ready' && ($condition['status'] ?? '') === 'True')) {
                throw new RuntimeException('The ingress class or ready cert-manager ClusterIssuer is unavailable. No workload was applied.');
            }
        }
        $migration = collect($plan)->firstWhere('body.kind', 'Job');
        if ($migration !== null) {
            $jobs = $client->get('/apis/batch/v1/namespaces/'.$namespace.'/jobs', ['labelSelector' => 'hosting.liberu.dev/owner='.$migration['body']['metadata']['labels']['hosting.liberu.dev/owner']]);
            if ($jobs->status() !== 404) {
                $this->successful($jobs->status());
                foreach ($jobs->json('items') ?? [] as $job) {
                    $finished = collect($job['status']['conditions'] ?? [])->contains(fn (array $condition): bool => in_array($condition['type'] ?? '', ['Complete', 'Failed'], true) && ($condition['status'] ?? '') === 'True');
                    if (! $finished && ($job['metadata']['name'] ?? '') !== $migration['body']['metadata']['name']) {
                        throw new RuntimeException('Another release migration is still running. No resources were changed.');
                    }
                }
            }
        }
        $result = $this->applyPlan($plan, $client, $dryRun);
        if ($dryRun || ($result['migration_pending'] ?? false)) {
            return $result;
        }
        $response = $client->get($deployment['path']);
        $this->successful($response->status());
        $observed = $response->json();
        $this->assertOwned($observed, $deployment['body']);
        $pod = $observed['spec']['template']['spec'] ?? [];
        if (! $this->contains($observed, $deployment['body']) || count($pod['containers'] ?? []) !== 1
            || ! empty($pod['initContainers']) || ! empty($pod['ephemeralContainers'])
            || count($pod['volumes'] ?? []) !== count($deployment['body']['spec']['template']['spec']['volumes'])) {
            throw new RuntimeException('The deployment no longer matches the restricted workload plan.');
        }
        $replicas = $deployment['body']['spec']['replicas'];
        $status = $observed['status'] ?? [];
        $generation = $observed['metadata']['generation'] ?? null;
        $ready = is_int($generation) && $generation > 0 && ($status['observedGeneration'] ?? 0) >= $generation
            && ($status['updatedReplicas'] ?? 0) === $replicas && ($status['readyReplicas'] ?? 0) === $replicas
            && ($status['availableReplicas'] ?? 0) === $replicas;

        if (isset($settings['ingress'])) {
            $certificate = $client->get('/apis/cert-manager.io/v1/namespaces/'.$namespace.'/certificates/app');
            $route = $client->get('/apis/networking.k8s.io/v1/namespaces/'.$namespace.'/ingresses/app');
            $certificateGeneration = $certificate->json('metadata.generation') ?? 0;
            $expiresAt = $certificate->json('status.notAfter');
            $expiresAt = is_string($expiresAt) ? strtotime($expiresAt) : false;
            $ready = $ready && $certificate->successful() && $route->successful() && $certificateGeneration > 0
                && collect($certificate->json('status.conditions') ?? [])->contains(fn (array $condition): bool => ($condition['type'] ?? '') === 'Ready' && ($condition['status'] ?? '') === 'True' && ($condition['observedGeneration'] ?? 0) >= $certificateGeneration)
                && $expiresAt !== false && $expiresAt > time() && ! empty($route->json('status.loadBalancer.ingress'));
            foreach (['Certificate' => $certificate, 'Ingress' => $route] as $kind => $readback) {
                $expected = array_values(array_filter($plan, fn (array $resource): bool => $resource['body']['kind'] === $kind))[0]['body'];
                $this->assertOwned($readback->json(), $expected);
                $ready = $ready && $this->contains($readback->json(), $expected);
            }
        }

        return ['applied' => true, 'ready' => $ready, 'dry_run' => false, 'namespace' => $namespace, 'resources' => $result['resources']];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $connection
     * @return array<string, mixed>
     */
    public function deployDataCluster(string $teamId, string $databaseId, string $driver, array $settings, array $connection, bool $dryRun = false): array
    {
        if (! in_array($driver, ['postgresql', 'redis', 'valkey'], true)) {
            throw new InvalidArgumentException('Select a supported database cluster driver.');
        }
        $plan = $this->dataPlans->build($teamId, $databaseId, $driver, $settings);
        $client = $this->client($connection);
        $resource = $plan[array_key_last($plan)];
        [$group, $version] = explode('/', $resource['body']['apiVersion']);
        $discovery = $client->get('/apis/'.$group.'/'.$version);
        $supported = $discovery->successful() && in_array($resource['body']['kind'], array_column($discovery->json('resources') ?? [], 'kind'), true);
        if (! $supported) {
            throw new RuntimeException('The required database operator API is unavailable. No resources were changed.');
        }
        $result = $this->applyPlan($plan, $client, $dryRun);
        if ($dryRun) {
            return [...$result, 'driver' => $driver];
        }
        $response = $client->get($resource['path']);
        $this->successful($response->status());
        $state = $response->json();
        $this->assertOwned($state, $resource['body']);
        if (! $this->contains($state, $resource['body'])) {
            throw new RuntimeException('The database cluster differs from its approved configuration.');
        }
        $generation = $state['metadata']['generation'] ?? 0;
        $conditions = collect($state['status']['conditions'] ?? []);
        $conditionReady = fn (string $type): bool => $generation > 0 && $conditions->contains(fn (array $condition): bool => ($condition['type'] ?? '') === $type && ($condition['status'] ?? '') === 'True' && ($condition['observedGeneration'] ?? 0) >= $generation);
        $status = $state['status'] ?? [];
        $ready = match ($driver) {
            'postgresql' => $conditionReady('Ready') && ($status['instances'] ?? 0) === 3 && ($status['readyInstances'] ?? 0) === 3 && is_string($status['currentPrimary'] ?? null) && $status['currentPrimary'] !== '',
            'valkey' => $conditionReady('Ready') && $conditionReady('ClusterFormed') && $conditionReady('SlotsAssigned')
                && ($status['state'] ?? '') === 'Ready' && ($status['readyShards'] ?? 0) === $resource['body']['spec']['shards'],
            'redis' => ($status['state'] ?? '') === 'Ready' && ($status['readyLeaderReplicas'] ?? 0) === $resource['body']['spec']['clusterSize']
                && ($status['readyFollowerReplicas'] ?? 0) === $resource['body']['spec']['clusterSize'] && $this->redisStatefulSetsReady($client, $result['namespace'], $state, $settings['image']),
        };

        return [...$result, 'ready' => $ready, 'driver' => $driver];
    }

    /** @param array<string, mixed> $cluster */
    private function redisStatefulSetsReady(PendingRequest $client, string $namespace, array $cluster, string $image): bool
    {
        $uid = $cluster['metadata']['uid'] ?? null;
        if (! is_string($uid) || $uid === '') {
            return false;
        }
        foreach (['leader', 'follower'] as $role) {
            $response = $client->get('/apis/apps/v1/namespaces/'.$namespace.'/statefulsets/app-'.$role);
            if (! $response->successful()) {
                return false;
            }
            $state = $response->json();
            $replicas = $cluster['spec']['clusterSize'];
            $generation = $state['metadata']['generation'] ?? 0;
            if ($generation < 1 || ! in_array($uid, array_column($state['metadata']['ownerReferences'] ?? [], 'uid'), true)
                || ($state['status']['observedGeneration'] ?? 0) < $generation || ($state['status']['readyReplicas'] ?? 0) !== $replicas
                || ($state['status']['updatedReplicas'] ?? 0) !== $replicas || ($state['spec']['replicas'] ?? 0) !== $replicas
                || ! is_string($state['status']['currentRevision'] ?? null) || $state['status']['currentRevision'] === ''
                || ($state['status']['currentRevision'] ?? null) !== ($state['status']['updateRevision'] ?? null)
                || ! in_array($image, array_column($state['spec']['template']['spec']['containers'] ?? [], 'image'), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{path: string, body: array<string, mixed>}>  $plan
     * @return array<string, mixed>
     */
    private function applyPlan(array $plan, PendingRequest $client, bool $dryRun): array
    {
        $namespace = $plan[0]['body']['metadata']['name'];
        $states = [];
        foreach ($plan as $resource) {
            $response = $client->get($resource['path']);
            if ($response->status() === 404) {
                $states[$resource['path']] = null;

                continue;
            }
            $this->successful($response->status());
            $this->assertOwned($response->json(), $resource['body']);
            if ($resource['body']['kind'] === 'Secret' && (! is_array($response->json('data')) || count($response->json('data')) !== count($resource['body']['data']) || ! $this->contains($response->json('data'), $resource['body']['data']))) {
                throw new RuntimeException('Existing credentials differ. Rotate credentials explicitly; no secret was overwritten.');
            }
            if (in_array($resource['body']['kind'], ['RedisCluster', 'ValkeyCluster', 'Cluster'], true)) {
                $expectedImage = $resource['body']['spec']['imageName'] ?? $resource['body']['spec']['image'] ?? $resource['body']['spec']['kubernetesConfig']['image'];
                $existingImage = $response->json('spec.imageName') ?? $response->json('spec.image') ?? $response->json('spec.kubernetesConfig.image');
                if ($expectedImage !== $existingImage) {
                    throw new RuntimeException('Database image upgrades require an explicit reviewed migration. No cluster image was changed.');
                }
            }
            $states[$resource['path']] = $response->json();
        }
        $results = [];
        if ($dryRun) {
            foreach ($plan as $resource) {
                $results[] = ['kind' => $resource['body']['kind'], 'name' => $resource['body']['metadata']['name'], 'status' => 'preflight-passed'];
            }

            return ['applied' => false, 'ready' => false, 'dry_run' => true, 'namespace' => $namespace, 'resources' => $results];
        }
        $namespaceResource = $plan[0];
        $createdNamespace = $states[$namespaceResource['path']] === null;
        if ($createdNamespace) {
            $response = $client->post('/api/v1/namespaces', $namespaceResource['body']);
            $this->successful($response->status());
            $this->assertOwned($response->json(), $namespaceResource['body']);
            if (! $this->contains($response->json(), $namespaceResource['body'])) {
                throw new RuntimeException('Admission changed namespace security settings. No workload was created.');
            }
        }
        // Validate admission of isolation controls before altering controls on an existing workload.
        foreach ($plan as $resource) {
            if (! in_array($resource['body']['kind'], ['Namespace', 'NetworkPolicy', 'ResourceQuota', 'LimitRange', 'ServiceAccount', 'Role', 'RoleBinding'], true)) {
                continue;
            }
            $response = $client->withBody(json_encode($resource['body'], JSON_THROW_ON_ERROR), 'application/apply-patch+yaml')
                ->patch($resource['path'].'?fieldManager=liberu-hosting&force=false&dryRun=All');
            $this->successful($response->status());
            $this->assertOwned($response->json(), $resource['body']);
            if (! $this->contains($response->json(), $resource['body'])) {
                throw new RuntimeException('Admission changed a required security or resource setting. Existing controls were not replaced.');
            }
        }
        foreach ($plan as $resource) {
            $body = $resource['body'];
            if ($body['kind'] === 'Namespace' && $createdNamespace) {
                $results[] = ['kind' => 'Namespace', 'name' => $namespace, 'status' => 'applied'];

                continue;
            }
            if ($body['kind'] === 'Job' && $states[$resource['path']] !== null) {
                // Retain the immutable release receipt; never restart a completed/failed job implicitly.
                $response = $client->get($resource['path']);
            } else {
                $response = $client->withBody(json_encode($body, JSON_THROW_ON_ERROR), 'application/apply-patch+yaml')
                    ->patch($resource['path'].'?fieldManager=liberu-hosting&force=false');
            }
            $this->successful($response->status());
            $this->assertOwned($response->json(), $body);
            if (! $this->contains($response->json(), $body)) {
                throw new RuntimeException('Admission changed a required security or resource setting. Inspect the namespace before retrying.');
            }
            $results[] = ['kind' => $body['kind'], 'name' => $body['metadata']['name'], 'status' => 'applied'];
            if ($body['kind'] === 'Job') {
                $readback = $client->get($resource['path']);
                $this->successful($readback->status());
                $this->assertOwned($readback->json(), $body);
                if (! $this->contains($readback->json(), $body)) {
                    throw new RuntimeException('The migration job differs from its approved release. No deployment was updated.');
                }
                $conditions = collect($readback->json('status.conditions') ?? []);
                if ($conditions->contains(fn (array $condition): bool => ($condition['type'] ?? '') === 'Failed' && ($condition['status'] ?? '') === 'True')) {
                    throw new RuntimeException('The release migration failed. Existing deployments were not updated. Inspect the protected job logs before retrying.');
                }
                if (! $conditions->contains(fn (array $condition): bool => ($condition['type'] ?? '') === 'Complete' && ($condition['status'] ?? '') === 'True')) {
                    return ['applied' => true, 'ready' => false, 'dry_run' => false, 'migration_pending' => true, 'namespace' => $namespace, 'resources' => $results];
                }
            }

        }

        return ['applied' => true, 'ready' => false, 'dry_run' => false, 'namespace' => $namespace, 'resources' => $results];
    }

    /** @param array<string, mixed> $connection */
    private function client(array $connection): PendingRequest
    {
        $url = $connection['url'] ?? '';
        $token = $connection['token'] ?? '';
        $parts = is_string($url) ? parse_url($url) : false;
        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || preg_match('/[\x00-\x20\x7f]/', $url) || ! is_string($token) || $token === '' || preg_match('/[\r\n]/', $token)) {
            throw new InvalidArgumentException('Configure an HTTPS Kubernetes endpoint and a private bearer token.');
        }
        $client = $this->http->baseUrl(rtrim($url, '/'))->withToken($token)->acceptJson()->asJson()->connectTimeout(3)->timeout(10)->withoutRedirecting();
        if (isset($connection['ca_certificate']) && $connection['ca_certificate'] !== '') {
            $path = $connection['ca_certificate'];
            if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
                throw new InvalidArgumentException('The configured Kubernetes CA certificate is unreadable.');
            }
            $client = $client->withOptions(['verify' => $path]);
        }

        return $client;
    }

    private function successful(int $status): void
    {
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Kubernetes rejected the operation (HTTP '.$status.'). No ownership conflicts were overridden; inspect partial state before retrying.');
        }
    }

    /** @param array<string, mixed> $expected */
    private function assertOwned(mixed $observed, array $expected): void
    {
        if (! is_array($observed) || ($observed['kind'] ?? '') !== $expected['kind']
            || ($observed['metadata']['name'] ?? '') !== $expected['metadata']['name']
            || ($observed['metadata']['namespace'] ?? null) !== ($expected['metadata']['namespace'] ?? null)
            || ($observed['metadata']['labels']['hosting.liberu.dev/owner'] ?? null) !== $expected['metadata']['labels']['hosting.liberu.dev/owner']) {
            throw new RuntimeException('A Kubernetes resource is owned by another tenant or installation. No adoption was attempted.');
        }
    }

    private function contains(mixed $observed, mixed $expected): bool
    {
        if (is_object($expected)) {
            return (is_array($observed) && $observed === []) || (is_object($observed) && get_object_vars($observed) === []);
        }
        if (! is_array($expected)) {
            return $observed === $expected;
        }
        if (! is_array($observed)) {
            return false;
        }
        if (array_is_list($expected) && count($observed) !== count($expected)) {
            return false;
        }
        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $observed) && $value === false && in_array($key, ['hostNetwork', 'hostPID', 'hostIPC'], true)) {
                continue;
            }
            if (! array_key_exists($key, $observed) || ! $this->contains($observed[$key], $value)) {
                return false;
            }
        }

        return true;
    }
}
