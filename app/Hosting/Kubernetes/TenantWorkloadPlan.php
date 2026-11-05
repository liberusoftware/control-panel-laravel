<?php

namespace App\Hosting\Kubernetes;

use InvalidArgumentException;

final class TenantWorkloadPlan
{
    /**
     * @param  array<string, mixed>  $settings
     * @return list<array{path: string, body: array<string, mixed>}>
     */
    public function build(string $teamId, string $workloadId, string $image, array $settings): array
    {
        $this->ensure($teamId !== '' && $workloadId !== '', 'Team and workload identities are required.');
        $this->ensure((bool) preg_match('/^[a-z0-9][a-z0-9.:\/_-]*@sha256:[a-f0-9]{64}$/D', $image), 'Deploy an immutable image digest from an operator-approved registry.');
        $this->ensure(in_array($image, $settings['allowed_images'] ?? [], true), 'This image is not allowlisted for the workload.');
        $runtime = $settings['runtime_class'] ?? '';
        $ingressNamespace = $settings['ingress_namespace'] ?? 'ingress-traefik';
        foreach ([$runtime, $ingressNamespace] as $name) {
            $this->ensure(is_string($name) && (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $name), 'Configure a sandbox RuntimeClass and ingress namespace.');
        }
        $this->ensure(($settings['network_policy_enforced'] ?? false) === true, 'An operator must confirm that the cluster enforces NetworkPolicy.');
        $replicas = $settings['replicas'] ?? 1;
        $port = $settings['port'] ?? 8080;
        $this->ensure(is_int($replicas) && $replicas >= 1 && $replicas <= 3, 'Replica count must be between one and three.');
        $this->ensure(is_int($port) && $port >= 1024 && $port <= 65535, 'The workload port must be an unprivileged TCP port.');
        $owner = substr(hash('sha256', $teamId.':'.$workloadId), 0, 32);
        $namespace = 'tenant-'.$owner;
        $labels = ['app.kubernetes.io/managed-by' => 'liberu', 'hosting.liberu.dev/owner' => $owner];
        $metadata = ['name' => 'app', 'namespace' => $namespace, 'labels' => $labels];
        $prefix = '/api/v1/namespaces/'.$namespace;
        $plan = [[
            'path' => '/api/v1/namespaces/'.$namespace,
            'body' => ['apiVersion' => 'v1', 'kind' => 'Namespace', 'metadata' => [
                'name' => $namespace, 'labels' => [...$labels,
                    'pod-security.kubernetes.io/enforce' => 'restricted',
                    'pod-security.kubernetes.io/enforce-version' => 'v1.30',
                    'pod-security.kubernetes.io/audit' => 'restricted',
                    'pod-security.kubernetes.io/warn' => 'restricted',
                ],
            ]],
        ]];
        $add = function (string $path, string $apiVersion, string $kind, array $spec) use (&$plan, $metadata): void {
            $plan[] = ['path' => $path, 'body' => ['apiVersion' => $apiVersion, 'kind' => $kind, 'metadata' => $metadata, ...$spec]];
        };
        $storageGi = $settings['storage_gi'] ?? 0;
        $this->ensure(is_int($storageGi) && $storageGi >= 0 && $storageGi <= 20, 'Persistent storage must be between zero and 20 GiB.');
        $add($prefix.'/resourcequotas/app', 'v1', 'ResourceQuota', ['spec' => ['hard' => [
            'pods' => '6', 'requests.cpu' => '2', 'limits.cpu' => '4', 'requests.memory' => '2Gi', 'limits.memory' => '4Gi',
            'requests.ephemeral-storage' => '2Gi', 'limits.ephemeral-storage' => '4Gi',
            'requests.storage' => $storageGi > 0 ? $storageGi.'Gi' : '0', 'persistentvolumeclaims' => $storageGi > 0 ? '1' : '0',
            'services' => '2', 'services.nodeports' => '0', 'services.loadbalancers' => '0', 'secrets' => '10', 'configmaps' => '10',
        ]]]);
        $add($prefix.'/limitranges/app', 'v1', 'LimitRange', ['spec' => ['limits' => [[
            'type' => 'Container', 'defaultRequest' => ['cpu' => '250m', 'memory' => '128Mi', 'ephemeral-storage' => '128Mi'],
            'default' => ['cpu' => '1', 'memory' => '512Mi', 'ephemeral-storage' => '512Mi'],
        ]]]]);
        $add($prefix.'/serviceaccounts/app', 'v1', 'ServiceAccount', ['automountServiceAccountToken' => false]);
        $connections = $settings['egress_connections'] ?? [];
        $this->ensure(is_array($connections) && count($connections) <= 16, 'Allowlist at most 16 internal service connections.');
        $extraEgress = [];
        foreach ($connections as $connection) {
            $targetNamespace = $connection['namespace'] ?? '';
            $targetPort = $connection['port'] ?? null;
            $this->ensure(is_string($targetNamespace) && (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $targetNamespace)
                && ! in_array($targetNamespace, ['default', 'kube-system', 'kube-public', 'kube-node-lease'], true)
                && in_array($targetPort, [443, 3306, 5432, 6379, 465, 587, 993], true), 'Approve a specific non-system namespace and supported database, mail or HTTPS service port.');
            $extraEgress[] = ['to' => [['namespaceSelector' => ['matchLabels' => ['kubernetes.io/metadata.name' => $targetNamespace]]]], 'ports' => [['protocol' => 'TCP', 'port' => $targetPort]]];
        }
        $ingress = $settings['ingress'] ?? null;
        if ($ingress !== null) {
            $this->ensure(is_array($ingress), 'Ingress settings must be operator controlled.');
            foreach (['class', 'issuer'] as $field) {
                $this->ensure(is_string($ingress[$field] ?? null) && (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $ingress[$field]), 'Select an existing ingress class and cert-manager ClusterIssuer.');
            }
            $host = $ingress['host'] ?? '';
            $this->ensure(is_string($host) && strlen($host) <= 253 && str_contains($host, '.') && (bool) preg_match('/^[a-z0-9.-]+$/D', $host), 'Use a fully qualified ASCII hostname for ingress.');
            foreach (explode('.', $host) as $label) {
                $this->ensure((bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label), 'Invalid ingress hostname label.');
            }
        }
        $add('/apis/networking.k8s.io/v1/namespaces/'.$namespace.'/networkpolicies/app', 'networking.k8s.io/v1', 'NetworkPolicy', ['spec' => [
            'podSelector' => (object) [], 'policyTypes' => ['Ingress', 'Egress'],
            'ingress' => [['from' => [['namespaceSelector' => ['matchLabels' => ['kubernetes.io/metadata.name' => $ingressNamespace]]]], 'ports' => $ingress === null ? [['protocol' => 'TCP', 'port' => $port]] : [['protocol' => 'TCP', 'port' => $port], ['protocol' => 'TCP', 'port' => 8089]]]],
            'egress' => [['to' => [[
                'namespaceSelector' => ['matchLabels' => ['kubernetes.io/metadata.name' => 'kube-system']],
                'podSelector' => ['matchLabels' => ['k8s-app' => 'kube-dns']],
            ]], 'ports' => [['protocol' => 'UDP', 'port' => 53], ['protocol' => 'TCP', 'port' => 53]]], ...$extraEgress],
        ]]);
        $volumes = [['name' => 'tmp', 'emptyDir' => ['sizeLimit' => '256Mi']]];
        $mounts = [['name' => 'tmp', 'mountPath' => '/tmp']];
        if ($storageGi > 0) {
            $storageClass = $settings['storage_class'] ?? '';
            $this->ensure(is_string($storageClass) && (bool) preg_match('/^[a-z0-9][a-z0-9.-]{0,252}$/D', $storageClass), 'An operator-selected StorageClass is required for persistent data.');
            $add($prefix.'/persistentvolumeclaims/app', 'v1', 'PersistentVolumeClaim', ['spec' => [
                'storageClassName' => $storageClass, 'accessModes' => [($replicas > 1 || isset($settings['laravel'])) ? 'ReadWriteMany' : 'ReadWriteOnce'],
                'resources' => ['requests' => ['storage' => $storageGi.'Gi']],
            ]]);
            $volumes[] = ['name' => 'data', 'persistentVolumeClaim' => ['claimName' => 'app']];
            $mounts[] = ['name' => 'data', 'mountPath' => '/data'];
        }
        $imagePullSecrets = [];
        if (isset($settings['registry_auth'])) {
            $auth = $settings['registry_auth'];
            $registry = explode('/', $image)[0];
            $this->ensure(is_array($auth) && ($auth['registry'] ?? null) === $registry
                && is_string($auth['username'] ?? null) && $auth['username'] !== '' && ! str_contains($auth['username'], ':')
                && is_string($auth['token'] ?? null) && $auth['token'] !== '' && strlen($auth['token']) <= 4096
                && ! preg_match('/[\x00-\x20\x7f]/', $auth['username'].$auth['token']), 'Registry credentials must match the approved image registry.');
            $dockerConfig = json_encode(['auths' => [$registry => ['username' => $auth['username'], 'password' => $auth['token'], 'auth' => base64_encode($auth['username'].':'.$auth['token'])]]], JSON_THROW_ON_ERROR);
            $add($prefix.'/secrets/app-registry', 'v1', 'Secret', ['metadata' => [...$metadata, 'name' => 'app-registry'], 'type' => 'kubernetes.io/dockerconfigjson', 'data' => ['.dockerconfigjson' => base64_encode($dockerConfig)]]);
            $imagePullSecrets = [['name' => 'app-registry']];
        }
        $environment = [];
        if (isset($settings['laravel'])) {
            $this->ensure(is_array($settings['laravel']), 'Laravel runtime configuration must be operator controlled.');
            $runtimeEnvironment = (new LaravelRuntimeEnvironment())->build($settings['laravel']);
            $values = $runtimeEnvironment['values'];
            $this->ensure($storageGi > 0, 'Laravel workloads require persistent storage.');
            $this->ensure($ingress === null || $values['APP_URL'] === 'https://'.$ingress['host'], 'Laravel site URL must match the approved ingress hostname.');
            $environmentName = 'app-env-'.substr(hash('sha256', json_encode($runtimeEnvironment, JSON_THROW_ON_ERROR)), 0, 16);
            $data = array_map(base64_encode(...), $values);
            $data['.env'] = base64_encode($runtimeEnvironment['contents']);
            $add($prefix.'/secrets/'.$environmentName, 'v1', 'Secret', ['metadata' => [...$metadata, 'name' => $environmentName], 'immutable' => true, 'type' => 'Opaque', 'data' => $data]);
            $add($prefix.'/secrets/app-key', 'v1', 'Secret', ['metadata' => [...$metadata, 'name' => 'app-key'], 'immutable' => true, 'type' => 'Opaque', 'data' => ['APP_KEY' => base64_encode($values['APP_KEY'])]]);
            foreach ($values as $name => $value) {
                $environment[] = ['name' => $name, 'valueFrom' => ['secretKeyRef' => ['name' => $name === 'APP_KEY' ? 'app-key' : $environmentName, 'key' => $name]]];
            }
            $volumes[] = ['name' => 'environment', 'secret' => ['secretName' => $environmentName, 'defaultMode' => 0440, 'items' => [['key' => '.env', 'path' => '.env']]]];
            $volumes[] = ['name' => 'bootstrap-cache', 'emptyDir' => ['sizeLimit' => '64Mi']];
            $mounts[] = ['name' => 'environment', 'mountPath' => '/var/www/html/.env', 'subPath' => '.env', 'readOnly' => true];
            $mounts[] = ['name' => 'data', 'mountPath' => '/var/www/html/storage'];
            $mounts[] = ['name' => 'bootstrap-cache', 'mountPath' => '/var/www/html/bootstrap/cache'];
        }
        $podSpec = [
            'os' => ['name' => 'linux'], 'nodeSelector' => ['kubernetes.io/os' => 'linux'],
            'runtimeClassName' => $runtime, 'serviceAccountName' => 'app', 'automountServiceAccountToken' => false,
            'hostNetwork' => false, 'hostPID' => false, 'hostIPC' => false, 'enableServiceLinks' => false,
            'securityContext' => ['runAsNonRoot' => true, 'runAsUser' => 10000, 'runAsGroup' => 10000, 'fsGroup' => 10000, 'seccompProfile' => ['type' => 'RuntimeDefault']],
            'containers' => [[
                'name' => 'app', 'image' => $image, 'imagePullPolicy' => 'Always',
                'ports' => [['name' => 'http', 'containerPort' => $port]],
                'securityContext' => ['allowPrivilegeEscalation' => false, 'readOnlyRootFilesystem' => true, 'privileged' => false, 'capabilities' => ['drop' => ['ALL']]],
                'resources' => ['requests' => ['cpu' => '250m', 'memory' => '128Mi', 'ephemeral-storage' => '128Mi'], 'limits' => ['cpu' => '1', 'memory' => '512Mi', 'ephemeral-storage' => '512Mi']],
                'readinessProbe' => ['httpGet' => ['path' => '/', 'port' => $port], 'periodSeconds' => 5],
                'volumeMounts' => $mounts,
            ]], 'volumes' => $volumes,
        ];
        if ($environment !== []) {
            $podSpec['containers'][0]['env'] = $environment;
        }
        if ($imagePullSecrets !== []) {
            $podSpec['imagePullSecrets'] = $imagePullSecrets;
        }
        if ($replicas > 1) {
            $podSpec['topologySpreadConstraints'] = [['maxSkew' => 1, 'minDomains' => 2, 'topologyKey' => 'kubernetes.io/hostname', 'whenUnsatisfiable' => 'DoNotSchedule', 'labelSelector' => ['matchLabels' => $labels]]];
        }
        if (isset($settings['laravel'])) {
            $policy = $settings['laravel']['migration_policy'] ?? 'never';
            $this->ensure(in_array($policy, ['never', 'first-create', 'each-release'], true), 'Choose an explicit Laravel migration seeding policy.');
            $seeder = $settings['laravel']['seeder'] ?? 'Database\\Seeders\\DatabaseSeeder';
            $this->ensure(is_string($seeder) && (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $seeder), 'Choose an approved Laravel seeder class.');
            $migrationPod = $podSpec;
            unset($migrationPod['topologySpreadConstraints']);
            unset($migrationPod['containers'][0]['readinessProbe'], $migrationPod['containers'][0]['ports']);
            $migrationPod['restartPolicy'] = 'Never';
            // Fixed bootstrap program, no shell or interpolated application code.
            $program = 'foreach (["storage/app/public", "storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "storage/logs", "bootstrap/cache"] as $directory) { if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) { exit(1); } } '
                .'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; '
                .'$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); '
                .'$m = $app->make("migrator"); '
                .'$first = !$m->repositoryExists() || $m->getRepository()->getRan() === []; '
                .'$seed = $argv[1] === "each-release" || ($argv[1] === "first-create" && $first); '
                .'$code = Illuminate\\Support\\Facades\\Artisan::call("migrate", ["--force" => true, "--no-interaction" => true] + ($seed ? ["--seed" => true, "--seeder" => $argv[2]] : [])); '
                .'echo Illuminate\\Support\\Facades\\Artisan::output(); exit($code);';
            $migrationPod['containers'][0]['command'] = ['php', '-r', $program, '--', $policy, $seeder];
            $migrationPod['containers'][0]['workingDir'] = '/var/www/html';
            $jobName = 'migrate-'.substr(hash('sha256', $image.':'.$environmentName.':'.$policy.':'.$seeder), 0, 24);
            $add('/apis/batch/v1/namespaces/'.$namespace.'/jobs/'.$jobName, 'batch/v1', 'Job', [
                'metadata' => [...$metadata, 'name' => $jobName], 'spec' => [
                    'backoffLimit' => 0, 'activeDeadlineSeconds' => 1800,
                    'template' => ['metadata' => ['labels' => $labels], 'spec' => $migrationPod],
                ],
            ]);
        }
        $add('/apis/apps/v1/namespaces/'.$namespace.'/deployments/app', 'apps/v1', 'Deployment', ['spec' => [
            'replicas' => $replicas, 'revisionHistoryLimit' => 2,
            'selector' => ['matchLabels' => $labels],
            'template' => ['metadata' => ['labels' => $labels], 'spec' => $podSpec],
        ]]);
        $add($prefix.'/services/app', 'v1', 'Service', ['spec' => ['type' => 'ClusterIP', 'selector' => $labels, 'ports' => [['name' => 'http', 'port' => 80, 'targetPort' => $port]]]]);

        if ($ingress !== null) {
            $add('/apis/cert-manager.io/v1/namespaces/'.$namespace.'/certificates/app', 'cert-manager.io/v1', 'Certificate', ['spec' => [
                'secretName' => 'app-tls', 'dnsNames' => [$ingress['host']], 'issuerRef' => ['name' => $ingress['issuer'], 'kind' => 'ClusterIssuer', 'group' => 'cert-manager.io'],
            ]]);
            $add('/apis/networking.k8s.io/v1/namespaces/'.$namespace.'/ingresses/app', 'networking.k8s.io/v1', 'Ingress', ['spec' => [
                'ingressClassName' => $ingress['class'], 'tls' => [['hosts' => [$ingress['host']], 'secretName' => 'app-tls']],
                'rules' => [['host' => $ingress['host'], 'http' => ['paths' => [['path' => '/', 'pathType' => 'Prefix', 'backend' => ['service' => ['name' => 'app', 'port' => ['number' => 80]]]]]]]],
            ]]);
        }
        if (isset($settings['access_user'])) {
            $user = $settings['access_user'];
            $this->ensure(is_string($user) && (bool) preg_match('/^[a-zA-Z0-9_.@-]{1,128}$/D', $user), 'Use an operator-approved Kubernetes user identity, never a system principal.');
            $add('/apis/rbac.authorization.k8s.io/v1/namespaces/'.$namespace.'/roles/app', 'rbac.authorization.k8s.io/v1', 'Role', ['rules' => [
                ['apiGroups' => [''], 'resources' => ['pods'], 'verbs' => ['get', 'list', 'watch']],
                ['apiGroups' => [''], 'resources' => ['pods/log'], 'verbs' => ['get']],
                ['apiGroups' => [''], 'resources' => ['pods/exec'], 'verbs' => ['create']],
            ]]);
            $add('/apis/rbac.authorization.k8s.io/v1/namespaces/'.$namespace.'/rolebindings/app', 'rbac.authorization.k8s.io/v1', 'RoleBinding', [
                'roleRef' => ['apiGroup' => 'rbac.authorization.k8s.io', 'kind' => 'Role', 'name' => 'app'],
                'subjects' => [['kind' => 'User', 'apiGroup' => 'rbac.authorization.k8s.io', 'name' => $user]],
            ]);
        }

        return $plan;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidArgumentException($message);
        }
    }
}
