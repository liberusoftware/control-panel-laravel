<?php

namespace App\Hosting\Kubernetes;

use InvalidArgumentException;

final class DataClusterPlan
{
    /** @param array<string, mixed> $settings
     * @return list<array{path: string, body: array<string, mixed>}>
     */
    public function build(string $teamId, string $databaseId, string $driver, array $settings): array
    {
        $this->ensure($teamId !== '' && $databaseId !== '' && in_array($driver, ['postgresql', 'redis', 'valkey'], true), 'A team-owned PostgreSQL, Redis or Valkey database is required.');
        $image = $settings['image'] ?? '';
        $this->ensure(is_string($image) && (bool) preg_match('/^[a-z0-9][a-z0-9.:\/_-]*@sha256:[a-f0-9]{64}$/D', $image)
            && in_array($image, $settings['allowed_images'] ?? [], true), 'Use an operator-approved immutable database image digest.');
        $this->ensure(($settings['network_policy_enforced'] ?? false) === true, 'The cluster must enforce NetworkPolicy.');
        $storage = $settings['storage_gi'] ?? 10;
        $storageClass = $settings['storage_class'] ?? '';
        $password = $settings['password'] ?? '';
        $this->ensure(is_int($storage) && $storage >= 1 && $storage <= 100, 'Per-instance persistent storage must be between one and 100 GiB.');
        $this->ensure(is_string($storageClass) && (bool) preg_match('/^[a-z0-9][a-z0-9.-]{0,252}$/D', $storageClass), 'Select a persistent StorageClass.');
        $this->ensure(is_string($password) && strlen($password) >= 32 && strlen($password) <= 1000 && ! preg_match('/[\x00-\x20\x7f]/', $password), 'Configure a stable database password of at least 32 characters without whitespace.');
        $shards = $settings['shards'] ?? 3;
        $this->ensure(is_int($shards) && $shards >= 3 && $shards <= 9, 'Redis and Valkey clusters need between three and nine primary shards.');
        $instances = $driver === 'postgresql' ? 3 : $shards * 2;
        $owner = substr(hash('sha256', $teamId.':'.$databaseId.':'.$driver), 0, 32);
        $namespace = 'data-'.$owner;
        $labels = ['app.kubernetes.io/managed-by' => 'liberu', 'hosting.liberu.dev/owner' => $owner];
        $meta = ['name' => 'app', 'namespace' => $namespace, 'labels' => $labels];
        $prefix = '/api/v1/namespaces/'.$namespace;
        $plan = [['path' => '/api/v1/namespaces/'.$namespace, 'body' => [
            'apiVersion' => 'v1', 'kind' => 'Namespace', 'metadata' => ['name' => $namespace, 'labels' => [...$labels,
                'pod-security.kubernetes.io/enforce' => 'restricted', 'pod-security.kubernetes.io/enforce-version' => 'v1.30',
            ]],
        ]]];
        $add = function (string $path, string $api, string $kind, array $fields) use (&$plan, $meta): void {
            $plan[] = ['path' => $path, 'body' => ['apiVersion' => $api, 'kind' => $kind, 'metadata' => $meta, ...$fields]];
        };
        $add($prefix.'/resourcequotas/app', 'v1', 'ResourceQuota', ['spec' => ['hard' => [
            'pods' => (string) ($instances + 2), 'requests.storage' => ($instances * $storage).'Gi',
            'persistentvolumeclaims' => (string) ($instances * 2), 'requests.cpu' => (string) $instances,
            'limits.cpu' => (string) ($instances * 2), 'requests.memory' => $instances.'Gi', 'limits.memory' => ($instances * 2).'Gi',
            'services.nodeports' => '0', 'services.loadbalancers' => '0',
        ]]]);
        $operatorNamespace = $settings['operator_namespace'] ?? '';
        $clients = $settings['client_namespaces'] ?? [];
        $this->ensure(is_array($clients) && count($clients) <= 16, 'Allowlist at most 16 client namespaces.');
        foreach ([$operatorNamespace, ...$clients] as $name) {
            $this->ensure(is_string($name) && (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $name), 'Configure an operator namespace and explicit client namespace names.');
        }
        $port = $driver === 'postgresql' ? 5432 : 6379;
        $internalPorts = $driver === 'postgresql' ? [5432, 8000] : [6379, 16379];
        $toPorts = fn (array $ports): array => array_map(fn (int $value): array => ['protocol' => 'TCP', 'port' => $value], $ports);
        $ingress = [
            ['from' => [['podSelector' => (object) []]], 'ports' => $toPorts($internalPorts)],
            ['from' => [['namespaceSelector' => ['matchLabels' => ['kubernetes.io/metadata.name' => $operatorNamespace]]]], 'ports' => $toPorts($internalPorts)],
        ];
        foreach ($clients as $client) {
            $ingress[] = ['from' => [['namespaceSelector' => ['matchLabels' => ['kubernetes.io/metadata.name' => $client]]]], 'ports' => $toPorts([$port])];
        }
        $egress = [
            ['to' => [['podSelector' => (object) []]], 'ports' => $toPorts($internalPorts)],
            ['to' => [['namespaceSelector' => ['matchLabels' => ['kubernetes.io/metadata.name' => 'kube-system']], 'podSelector' => ['matchLabels' => ['k8s-app' => 'kube-dns']]]], 'ports' => [['protocol' => 'UDP', 'port' => 53], ['protocol' => 'TCP', 'port' => 53]]],
        ];
        if ($driver === 'postgresql') {
            $apiIp = $settings['api_server_ip'] ?? '';
            $this->ensure(is_string($apiIp) && filter_var($apiIp, FILTER_VALIDATE_IP) !== false, 'Configure the Kubernetes API service IP for PostgreSQL instance-manager access.');
            $egress[] = ['to' => [['ipBlock' => ['cidr' => $apiIp.(str_contains($apiIp, ':') ? '/128' : '/32')]]], 'ports' => $toPorts([443, 6443])];
        }
        $add('/apis/networking.k8s.io/v1/namespaces/'.$namespace.'/networkpolicies/app', 'networking.k8s.io/v1', 'NetworkPolicy', ['spec' => [
            'podSelector' => (object) [], 'policyTypes' => ['Ingress', 'Egress'], 'ingress' => $ingress, 'egress' => $egress,
        ]]);
        $credentials = ['password' => base64_encode($password)];
        if ($driver === 'postgresql') {
            $credentials['username'] = base64_encode('app');
        }
        $add($prefix.'/secrets/app', 'v1', 'Secret', ['type' => $driver === 'postgresql' ? 'kubernetes.io/basic-auth' : 'Opaque', 'data' => $credentials]);
        if ($driver === 'postgresql') {
            $database = $settings['database'] ?? '';
            $this->ensure(is_string($database) && (bool) preg_match('/^[a-z][a-z0-9_]{0,62}$/D', $database), 'Use a PostgreSQL database name containing lowercase letters, digits and underscores.');
            $add('/apis/postgresql.cnpg.io/v1/namespaces/'.$namespace.'/clusters/app', 'postgresql.cnpg.io/v1', 'Cluster', ['spec' => [
                'instances' => 3, 'imageName' => $image, 'enableSuperuserAccess' => false,
                'bootstrap' => ['initdb' => ['database' => $database, 'owner' => 'app', 'secret' => ['name' => 'app'], 'dataChecksums' => true]],
                'storage' => ['size' => $storage.'Gi', 'storageClass' => $storageClass],
                'affinity' => ['enablePodAntiAffinity' => true, 'podAntiAffinityType' => 'required', 'topologyKey' => 'kubernetes.io/hostname'],
                'postgresql' => ['synchronous' => ['method' => 'any', 'number' => 1, 'dataDurability' => 'required'], 'pg_hba' => ['hostnossl all all all reject']],
                'resources' => ['requests' => ['cpu' => '500m', 'memory' => '512Mi'], 'limits' => ['cpu' => '2', 'memory' => '2Gi']],
            ]]);
        } else {
            $tls = $this->tls($settings);
            $add($prefix.'/secrets/app-tls', 'v1', 'Secret', ['metadata' => [...$meta, 'name' => 'app-tls'], 'type' => 'kubernetes.io/tls', 'data' => $tls]);
            $resources = ['requests' => ['cpu' => '250m', 'memory' => '256Mi'], 'limits' => ['cpu' => '1', 'memory' => '1Gi']];
            if ($driver === 'valkey') {
                $add('/apis/valkey.io/v1alpha1/namespaces/'.$namespace.'/valkeyclusters/app', 'valkey.io/v1alpha1', 'ValkeyCluster', ['spec' => [
                    'image' => $image, 'shards' => $shards, 'replicas' => 1, 'workloadType' => 'StatefulSet',
                    'resources' => $resources, 'config' => ['appendonly' => 'yes', 'appendfsync' => 'everysec', 'maxmemory-policy' => 'noeviction', 'maxmemory' => '512mb', 'min-replicas-to-write' => '1', 'min-replicas-max-lag' => '10'],
                    'persistence' => ['size' => $storage.'Gi', 'storageClassName' => $storageClass, 'reclaimPolicy' => 'Retain'],
                    'podSecurityContext' => ['runAsNonRoot' => true, 'runAsUser' => 1000, 'runAsGroup' => 1000, 'fsGroup' => 1000, 'seccompProfile' => ['type' => 'RuntimeDefault']],
                    'scheduling' => ['node' => ['spread' => ['shard' => ['mode' => 'Required'], 'primaries' => ['mode' => 'Required']]]],
                    'networking' => ['discovery' => ['preferredEndpointType' => 'Hostname'], 'tls' => ['certificates' => ['server' => ['secretName' => 'app-tls']]]],
                    'users' => [['name' => 'default', 'enabled' => true, 'passwordSecret' => ['name' => 'app', 'keys' => ['password']], 'permissions' => '~* &* +@all']],
                ]]);
            } else {
                $version = $settings['redis_major_version'] ?? '';
                $this->ensure(in_array($version, ['v7', 'v8'], true), 'Set redis_major_version to match the approved Redis image (v7 or v8).');
                $add($prefix.'/configmaps/app-config', 'v1', 'ConfigMap', ['metadata' => [...$meta, 'name' => 'app-config'], 'data' => ['redis-additional.conf' => "appendonly yes\ncluster-preferred-endpoint-type hostname\nappendfsync everysec\nmaxmemory 512mb\nmaxmemory-policy noeviction\nmin-replicas-to-write 1\nmin-replicas-max-lag 10\ntls-auth-clients no\n"]]);
                $affinity = ['podAntiAffinity' => ['requiredDuringSchedulingIgnoredDuringExecution' => [['labelSelector' => ['matchLabels' => ['redis_setup_type' => 'cluster']], 'topologyKey' => 'kubernetes.io/hostname']]]];
                $add('/apis/redis.redis.opstreelabs.in/v1beta2/namespaces/'.$namespace.'/redisclusters/app', 'redis.redis.opstreelabs.in/v1beta2', 'RedisCluster', ['spec' => [
                    'clusterSize' => $shards, 'clusterVersion' => $version, 'persistenceEnabled' => true,
                    'kubernetesConfig' => ['image' => $image, 'imagePullPolicy' => 'Always', 'redisSecret' => ['name' => 'app', 'key' => 'password']],
                    'redisLeader' => ['replicas' => $shards, 'affinity' => $affinity, 'resources' => $resources, 'redisConfig' => ['additionalRedisConfig' => 'app-config']],
                    'redisFollower' => ['replicas' => $shards, 'affinity' => $affinity, 'resources' => $resources, 'redisConfig' => ['additionalRedisConfig' => 'app-config']],
                    'storage' => ['keepAfterDelete' => true, 'volumeClaimTemplate' => ['spec' => ['storageClassName' => $storageClass, 'accessModes' => ['ReadWriteOnce'], 'resources' => ['requests' => ['storage' => $storage.'Gi']]]]],
                    'TLS' => ['ca' => 'ca.crt', 'cert' => 'tls.crt', 'key' => 'tls.key', 'secret' => ['secretName' => 'app-tls']],
                    'podSecurityContext' => ['runAsNonRoot' => true, 'runAsUser' => 1000, 'runAsGroup' => 1000, 'fsGroup' => 1000, 'seccompProfile' => ['type' => 'RuntimeDefault']],
                ]]);
            }
        }

        return $plan;
    }

    /** @param array<string, mixed> $settings
     * @return array<string, string>
     */
    private function tls(array $settings): array
    {
        $ca = $settings['tls_ca'] ?? '';
        $certificate = $settings['tls_certificate'] ?? '';
        $key = $settings['tls_key'] ?? '';
        $this->ensure(is_string($ca) && is_string($certificate) && is_string($key), 'Provide cluster TLS certificate, key and CA PEM values.');
        $cert = @openssl_x509_read($certificate);
        $caKey = @openssl_pkey_get_public($ca);
        $privateKey = @openssl_pkey_get_private($key);
        $parsed = $cert === false ? false : openssl_x509_parse($cert);
        $this->ensure($cert !== false && $caKey !== false && $privateKey !== false
            && openssl_x509_check_private_key($cert, $privateKey) && openssl_x509_verify($cert, $caKey) === 1
            && is_array($parsed) && $parsed['validFrom_time_t'] <= time() && $parsed['validTo_time_t'] > time(), 'Cluster TLS material must be current, match its private key and be signed by the supplied CA.');

        return ['ca.crt' => base64_encode($ca), 'tls.crt' => base64_encode($certificate), 'tls.key' => base64_encode($key)];
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidArgumentException($message);
        }
    }
}
