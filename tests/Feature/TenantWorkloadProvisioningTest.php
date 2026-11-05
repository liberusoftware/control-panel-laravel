<?php

use App\Hosting\Kubernetes\KubernetesProvisioner;
use App\Hosting\Kubernetes\TenantWorkloadPlan;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;
use Liberu\ControlPanel\Kubernetes\Actions\ArchiveCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterKubernetesAsset;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;

beforeEach(function (): void {
    app()->register(KubernetesServiceProvider::class);
    app()->register(ControlCoreServiceProvider::class);
    $this->artisan('migrate');
    $this->image = 'registry.example.test/site@sha256:'.str_repeat('a', 64);
    $this->settings = ['allowed_images' => [$this->image], 'runtime_class' => 'gvisor', 'network_policy_enforced' => true, 'replicas' => 1, 'port' => 8080];
    $this->connection = ['url' => 'https://cluster.example.test', 'token' => 'private-cluster-token'];
    $this->cluster = app(RegisterCluster::class)->execute(['team_id' => 'team-a', 'name' => 'Sandbox', 'endpoint' => $this->connection['url']]);
    $this->workload = app(RegisterKubernetesAsset::class)->execute([
        'team_id' => 'team-a', 'cluster_id' => $this->cluster->getKey(), 'kind' => 'workload',
        'namespace' => 'inventory-only', 'name' => 'website', 'image' => $this->image, 'replicas' => 1,
    ]);
    $this->plan = app(TenantWorkloadPlan::class)->build('team-a', $this->workload->getKey(), $this->image, $this->settings);
    $this->remote = [];
    $this->ready = true;
    $this->onDeploymentApplied = null;
    $this->migrationReady = true;
    $this->migrationFailed = false;
    $this->runtimeAvailable = true;
    $this->issuerAvailable = true;
    $this->tlsReady = true;
    $this->certificateExpired = false;
    $this->conflictKind = null;
    $this->mutateKind = null;
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $path = parse_url($request->url(), PHP_URL_PATH);
        if (str_contains($path, '/clusterissuers/')) {
            return $this->issuerAvailable ? Http::response(['metadata' => ['name' => 'letsencrypt'], 'status' => ['conditions' => [['type' => 'Ready', 'status' => 'True']]]]) : Http::response([], 404);
        }
        if (str_contains($path, '/ingressclasses/')) {
            return Http::response(['metadata' => ['name' => 'nginx']]);
        }
        if (str_contains($path, '/runtimeclasses/')) {
            return $this->runtimeAvailable ? Http::response(['metadata' => ['name' => 'gvisor'], 'handler' => 'runsc']) : Http::response([], 404);
        }
        if ($request->method() === 'GET' && str_ends_with($path, '/jobs')) {
            return Http::response(['items' => array_values(array_filter($this->remote, fn (array $item): bool => $item['kind'] === 'Job'))]);
        }
        if ($request->method() === 'GET') {
            return isset($this->remote[$path]) ? Http::response($this->remote[$path]) : Http::response([], 404);
        }
        $body = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);
        if ($body['kind'] === $this->conflictKind) {
            return Http::response(['message' => 'private upstream data'], 409);
        }
        if ($body['kind'] === $this->mutateKind) {
            $body['spec']['egress'][] = ['to' => []];
        }
        if ($body['kind'] === 'Namespace' && $request->method() === 'POST') {
            $path .= '/'.$body['metadata']['name'];
        }
        if ($body['kind'] === 'Job') {
            $body['status'] = ['conditions' => [['type' => $this->migrationFailed ? 'Failed' : 'Complete', 'status' => $this->migrationReady || $this->migrationFailed ? 'True' : 'False']]];
        }
        if ($body['kind'] === 'Deployment') {
            $body['metadata']['generation'] = 2;
            $body['status'] = ['observedGeneration' => $this->ready ? 2 : 1, 'updatedReplicas' => 1, 'readyReplicas' => 1, 'availableReplicas' => 1];
        }
        if ($body['kind'] === 'Certificate') {
            $body['metadata']['generation'] = 2;
            $body['status'] = ['notAfter' => gmdate(DATE_ATOM, time() + ($this->certificateExpired ? -86400 : 86400)), 'conditions' => [['type' => 'Ready', 'status' => $this->tlsReady ? 'True' : 'False', 'observedGeneration' => 2]]];
        }
        if ($body['kind'] === 'Ingress') {
            $body['status'] = ['loadBalancer' => ['ingress' => [['ip' => '203.0.113.10']]]];
        }
        if (str_contains($request->url(), 'dryRun=All')) {
            return Http::response($body);
        }
        if ($body['kind'] === 'Deployment' && $this->onDeploymentApplied !== null) {
            ($this->onDeploymentApplied)();
        }
        $this->remote[$path] = $body;

        return Http::response($body, $request->method() === 'POST' ? 201 : 200);
    });
    config([
        'cache.default' => 'redis',
        'hosting-kubernetes.sources.test' => ['team_id' => 'team-a', 'cluster_id' => $this->cluster->getKey(), 'workload_id' => $this->workload->getKey(), 'connection' => 'test', 'settings' => $this->settings],
        'hosting-kubernetes.connections.test' => $this->connection,
    ]);
    Cache::swap(new Repository(new ArrayStore()));
});

function provisionTenantFixture(object $test, bool $dryRun = false): array
{
    return app(KubernetesProvisioner::class)->deploy('team-a', $test->workload->getKey(), $test->image, $test->settings, $test->connection, $dryRun);
}

it('applies an isolated namespace and verifies current-generation readiness', function (): void {
    $result = provisionTenantFixture($this);
    expect($result['applied'])->toBeTrue()->and($result['ready'])->toBeTrue();
    $deployment = collect($this->remote)->firstWhere('kind', 'Deployment');
    $pod = $deployment['spec']['template']['spec'];
    expect($pod['hostNetwork'])->toBeFalse()->and($pod['hostPID'])->toBeFalse()
        ->and($pod['automountServiceAccountToken'])->toBeFalse()
        ->and($pod['runtimeClassName'])->toBe('gvisor')
        ->and($pod['securityContext']['runAsUser'])->toBe(10000)
        ->and($pod['containers'][0]['securityContext']['readOnlyRootFilesystem'])->toBeTrue()
        ->and($pod['containers'][0]['securityContext']['capabilities']['drop'])->toBe(['ALL']);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
        && str_contains($request->url(), 'force=false')
        && $request->hasHeader('Content-Type', 'application/apply-patch+yaml'));
});

it('performs read-only dry-run checks without creating resources', function (): void {
    $result = provisionTenantFixture($this, true);
    expect($result['dry_run'])->toBeTrue()->and($result['ready'])->toBeFalse()->and($this->remote)->toBe([]);
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('uses distinct namespaces for different tenants even with the same workload identity', function (): void {
    $other = app(TenantWorkloadPlan::class)->build('team-b', $this->workload->getKey(), $this->image, $this->settings);
    expect($other[0]['body']['metadata']['name'])->not->toBe($this->plan[0]['body']['metadata']['name']);
});

it('does not adopt an existing namespace or resource owned by another tenant', function (int $resourceIndex): void {
    $resource = $this->plan[$resourceIndex];
    $resource['body']['metadata']['labels']['hosting.liberu.dev/owner'] = 'foreign-team';
    $this->remote[$resource['path']] = $resource['body'];
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'another tenant');
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
})->with([0, 4]);

it('fails before changing resources when the sandbox runtime is unavailable', function (): void {
    $this->runtimeAvailable = false;
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'RuntimeClass');
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('does not override ownership conflicts or proceed to deploy after a policy failure', function (): void {
    $this->conflictKind = 'NetworkPolicy';
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'HTTP 409');
    expect(collect($this->remote)->firstWhere('kind', 'Deployment'))->toBeNull();
});

it('detects admission that weakens a network policy', function (): void {
    $this->mutateKind = 'NetworkPolicy';
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'Admission changed');
    expect(collect($this->remote)->firstWhere('kind', 'Deployment'))->toBeNull();
});

it('does not report stale deployment status as ready', function (): void {
    $this->ready = false;
    $result = provisionTenantFixture($this);
    expect($result['applied'])->toBeTrue()->and($result['ready'])->toBeFalse();
});

it('rejects mutable unapproved images or missing isolation prerequisites before network calls', function (string $failure): void {
    match ($failure) {
        'mutable-image' => $this->image = 'registry.example.test/site:latest',
        'unapproved-image' => $this->settings['allowed_images'] = [],
        'network' => $this->settings['network_policy_enforced'] = false,
        'runtime' => $this->settings['runtime_class'] = '',
        'port' => $this->settings['port'] = 22,
    };
    expect(fn () => provisionTenantFixture($this))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with(['mutable-image', 'unapproved-image', 'network', 'runtime', 'port']);

it('persists sanitized operator audit evidence for a successful provisioning command', function (): void {
    $this->artisan('hosting:deploy-workload test')->assertSuccessful();
    $entry = AuditEntry::query()->where('event', 'hosting.workload.provisioned')->firstOrFail();
    expect($entry->team_id)->toBe('team-a')->and($entry->context['ready'])->toBeTrue()
        ->and(json_encode($entry))->not->toContain('private-cluster-token');
});

it('rejects a forged operator source ownership or cluster endpoint', function (string $field, string $value): void {
    config(['hosting-kubernetes.sources.test.'.$field => $value]);
    $this->artisan('hosting:deploy-workload test')->assertFailed();
    Http::assertNothingSent();
})->with([['team_id', 'team-b'], ['cluster_id', 'missing-cluster']]);

it('grants optional end-user access only to pods logs and exec within the isolated namespace', function (): void {
    $this->settings['access_user'] = 'customer@example.test';
    $plan = app(TenantWorkloadPlan::class)->build('team-a', $this->workload->getKey(), $this->image, $this->settings);
    $role = collect($plan)->first(fn (array $resource): bool => $resource['body']['kind'] === 'Role')['body'];
    $binding = collect($plan)->first(fn (array $resource): bool => $resource['body']['kind'] === 'RoleBinding')['body'];
    expect($role['rules'])->toBe([
        ['apiGroups' => [''], 'resources' => ['pods'], 'verbs' => ['get', 'list', 'watch']],
        ['apiGroups' => [''], 'resources' => ['pods/log'], 'verbs' => ['get']],
        ['apiGroups' => [''], 'resources' => ['pods/exec'], 'verbs' => ['create']],
    ])->and($binding['subjects'][0]['name'])->toBe('customer@example.test')
        ->and($binding['metadata']['namespace'])->toBe($plan[0]['body']['metadata']['name']);
});

it('does not grant workload access to reserved Kubernetes system identities', function (): void {
    $this->settings['access_user'] = 'system:masters';
    expect(fn () => provisionTenantFixture($this))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
});

it('uses private registry credentials only for image pulls and omits them from results', function (): void {
    $this->settings['registry_auth'] = ['registry' => 'registry.example.test', 'username' => 'deploy-user', 'token' => 'private-registry-token'];
    $result = provisionTenantFixture($this);
    expect($result['ready'])->toBeTrue()->and(json_encode($result))->not->toContain('private-registry-token');
    $deployment = collect($this->remote)->firstWhere('kind', 'Deployment');
    expect($deployment['spec']['template']['spec']['imagePullSecrets'])->toBe([['name' => 'app-registry']])
        ->and($deployment['spec']['template']['spec']['containers'][0])->not->toHaveKeys(['env', 'envFrom']);
    $secret = collect($this->remote)->firstWhere('kind', 'Secret');
    $dockerConfig = json_decode(base64_decode($secret['data']['.dockerconfigjson']), true);
    expect($dockerConfig['auths']['registry.example.test']['password'])->toBe('private-registry-token');
});

it('refuses registry credentials for a host other than the allowlisted image registry', function (): void {
    $this->settings['registry_auth'] = ['registry' => 'foreign.example.test', 'username' => 'deploy-user', 'token' => 'private-registry-token'];
    expect(fn () => provisionTenantFixture($this))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
});

it('allows only operator-approved internal service namespaces and ports for tenant egress', function (): void {
    $this->settings['egress_connections'] = [['namespace' => 'data-private', 'port' => 5432], ['namespace' => 'cache-private', 'port' => 6379]];
    $plan = app(TenantWorkloadPlan::class)->build('team-a', $this->workload->getKey(), $this->image, $this->settings);
    $policy = collect($plan)->first(fn (array $resource): bool => $resource['body']['kind'] === 'NetworkPolicy')['body'];
    expect($policy['spec']['egress'])->toHaveCount(3)
        ->and($policy['spec']['egress'][1]['to'][0]['namespaceSelector']['matchLabels']['kubernetes.io/metadata.name'])->toBe('data-private')
        ->and($policy['spec']['egress'][1]['ports'])->toBe([['protocol' => 'TCP', 'port' => 5432]]);
});

it('refuses system namespaces and arbitrary service ports in tenant egress', function (string $namespace, int $port): void {
    $this->settings['egress_connections'] = [['namespace' => $namespace, 'port' => $port]];
    expect(fn () => provisionTenantFixture($this))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([['kube-system', 443], ['default', 6379], ['data-private', 22]]);

it('provisions optional HTTPS ingress and a cert-manager certificate for the approved hostname', function (): void {
    $this->settings['ingress'] = ['host' => 'site.example.test', 'class' => 'nginx', 'issuer' => 'letsencrypt'];
    expect(provisionTenantFixture($this)['ready'])->toBeTrue();
    $certificate = collect($this->remote)->firstWhere('kind', 'Certificate');
    $ingress = collect($this->remote)->firstWhere('kind', 'Ingress');
    expect($certificate['spec']['dnsNames'])->toBe(['site.example.test'])
        ->and($ingress['spec']['tls'][0])->toBe(['hosts' => ['site.example.test'], 'secretName' => 'app-tls']);
});

it('does not claim HTTPS readiness for pending or expired certificates', function (bool $expired): void {
    $this->settings['ingress'] = ['host' => 'site.example.test', 'class' => 'nginx', 'issuer' => 'letsencrypt'];
    $this->certificateExpired = $expired;
    $this->tlsReady = $expired;
    expect(provisionTenantFixture($this)['ready'])->toBeFalse();
})->with([false, true]);

it('fails before creating a namespace when the approved certificate issuer is unavailable', function (): void {
    $this->settings['ingress'] = ['host' => 'site.example.test', 'class' => 'nginx', 'issuer' => 'letsencrypt'];
    $this->issuerAvailable = false;
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'ClusterIssuer');
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('preflights weakened policy admission without replacing controls on an existing workload', function (): void {
    expect(provisionTenantFixture($this)['ready'])->toBeTrue();
    $before = $this->remote;
    $this->mutateKind = 'NetworkPolicy';
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'Admission changed');
    expect($this->remote)->toBe($before);
});

it('records sanitized failure evidence when a partially created workload cannot finish', function (): void {
    $this->conflictKind = 'NetworkPolicy';
    $this->artisan('hosting:deploy-workload test')->assertFailed();
    $entry = AuditEntry::query()->where('event', 'hosting.workload.failed')->firstOrFail();
    expect($entry->team_id)->toBe('team-a')
        ->and($entry->context['partial_state_possible'])->toBeTrue()
        ->and(json_encode($entry))->not->toContain('private-cluster-token', 'private upstream data');
});

function configureLaravelTenantFixture(object $test): void
{
    $test->environmentFile = tempnam(sys_get_temp_dir(), 'hosting-tenant-');
    chmod($test->environmentFile, 0600);
    file_put_contents($test->environmentFile, "STRIPE_SECRET=private-stripe-token\n");
    $test->settings = [...$test->settings, 'storage_gi' => 2, 'storage_class' => 'shared', 'laravel' => [
        'environment_file' => $test->environmentFile, 'app_key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'app_url' => 'https://site.example.test', 'migration_policy' => 'first-create',
        'database' => ['connection' => 'pgsql', 'host' => 'app-rw.database.svc', 'database' => 'site', 'username' => 'site', 'password' => 'private-database-password'],
    ]];
}

afterEach(function (): void {
    if (isset($this->environmentFile)) {
        @unlink($this->environmentFile);
    }
});

it('gates Laravel deployment on completion of its release migration', function (): void {
    configureLaravelTenantFixture($this);
    $this->migrationReady = false;
    $pending = provisionTenantFixture($this);
    expect($pending['ready'])->toBeFalse()->and($pending['migration_pending'])->toBeTrue();
    expect(collect($this->remote)->where('kind', 'Deployment'))->toBeEmpty();
    $this->migrationReady = true;
    foreach ($this->remote as &$resource) {
        if ($resource['kind'] === 'Job') {
            $resource['status']['conditions'] = [['type' => 'Complete', 'status' => 'True']];
        }
    }
    unset($resource);
    $complete = provisionTenantFixture($this);
    expect($complete['ready'])->toBeTrue()->and(collect($this->remote)->where('kind', 'Job'))->toHaveCount(1);
    expect(json_encode($complete))->not->toContain('private-stripe-token', 'private-database-password');
});

it('does not roll out a Laravel release when migrations fail', function (): void {
    configureLaravelTenantFixture($this);
    $this->migrationFailed = true;
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'migration failed');
    expect(collect($this->remote)->where('kind', 'Deployment'))->toBeEmpty();
});

it('refuses implicit Laravel key rotation before changing any existing resources', function (): void {
    configureLaravelTenantFixture($this);
    provisionTenantFixture($this);
    $before = $this->remote;
    $this->settings['laravel']['app_key'] = 'base64:'.base64_encode(str_repeat('b', 32));
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'credentials differ');
    expect($this->remote)->toBe($before);
});

it('refuses a second release while a previous migration is running', function (): void {
    configureLaravelTenantFixture($this);
    $this->migrationReady = false;
    provisionTenantFixture($this);
    $before = $this->remote;
    file_put_contents($this->environmentFile, "STRIPE_SECRET=updated-stripe-token\n");
    expect(fn () => provisionTenantFixture($this))->toThrow(RuntimeException::class, 'still running');
    expect($this->remote)->toBe($before);
});

it('refuses suspended and archived clusters before any workload request', function (string $action): void {
    app($action)->execute($this->cluster);
    $this->artisan('hosting:deploy-workload test')->assertFailed();
    Http::assertNothingSent();
})->with([SuspendCluster::class, ArchiveCluster::class]);

it('does not report success if the cluster is suspended during workload provisioning', function (): void {
    $this->onDeploymentApplied = fn () => app(SuspendCluster::class)->execute($this->cluster);
    $this->artisan('hosting:deploy-workload test')->assertFailed();
    $audit = AuditEntry::query()->where('event', 'hosting.workload.provisioned')->firstOrFail();
    expect($audit->getAttribute('context'))->toMatchArray(['ready' => false, 'inventory_unchanged' => false]);
});

it('enforces the approved cap against canonical desired workload replicas', function (): void {
    $this->workload->forceFill(['replicas' => 2])->save();
    config(['hosting-kubernetes.sources.test.settings.max_replicas' => 1]);
    $this->artisan('hosting:deploy-workload test')->assertFailed();
    Http::assertNothingSent();
});

it('provisions scalable persistent web storage before the first scale operation', function (int $cap, string $mode): void {
    $plan = app(TenantWorkloadPlan::class)->build('team-a', $this->workload->getKey(), $this->image,
        [...$this->settings, 'storage_gi' => 2, 'storage_class' => 'durable', 'max_replicas' => $cap]);
    $claim = collect($plan)->firstWhere('body.kind', 'PersistentVolumeClaim');
    expect($claim['body']['spec']['accessModes'])->toBe([$mode]);
})->with([[1, 'ReadWriteOnce'], [3, 'ReadWriteMany']]);
