<?php

use App\Hosting\Kubernetes\DataClusterPlan;
use App\Hosting\Kubernetes\KubernetesProvisioner;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\DatabasesServiceProvider;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;
use Liberu\ControlPanel\Kubernetes\Actions\RegisterCluster;
use Liberu\ControlPanel\Kubernetes\KubernetesServiceProvider;

beforeEach(function (): void {
    foreach ([ControlCoreServiceProvider::class, DatabasesServiceProvider::class, KubernetesServiceProvider::class] as $provider) {
        app()->register($provider);
    }
    $this->artisan('migrate');
    static $tls;
    if ($tls === null) {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'fixture.example.test'], $key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        openssl_x509_export($cert, $pem);
        openssl_pkey_export($key, $private);
        $tls = ['tls_ca' => $pem, 'tls_certificate' => $pem, 'tls_key' => $private];
    }
    $this->image = 'registry.example.test/database@sha256:'.str_repeat('b', 64);
    $this->settings = [...$tls, 'image' => $this->image, 'allowed_images' => [$this->image], 'network_policy_enforced' => true,
        'storage_class' => 'durable', 'storage_gi' => 10, 'password' => str_repeat('private-password', 3),
        'operator_namespace' => 'data-operators', 'api_server_ip' => '10.96.0.1', 'database' => 'website', 'redis_major_version' => 'v8'];
    $this->connection = ['url' => 'https://cluster.example.test', 'token' => 'private-token'];
    $this->cluster = app(RegisterCluster::class)->execute(['team_id' => 'team-a', 'name' => 'Production', 'endpoint' => $this->connection['url']]);
    $this->engine = DatabaseEngine::query()->create(['team_id' => 'team-a', 'name' => 'PostgreSQL', 'driver' => 'postgresql', 'version' => '18', 'host' => 'pending', 'port' => 5432, 'active' => true]);
    $this->database = app(CreateDatabase::class)->execute(['team_id' => 'team-a', 'engine_id' => $this->engine->getKey(), 'name' => 'website']);
    $this->remote = [];
    $this->operatorAvailable = true;
    $this->ready = true;
    $this->stale = false;
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $path = parse_url($request->url(), PHP_URL_PATH);
        if (preg_match('~^/apis/[^/]+/[^/]+$~', $path)) {
            return $this->operatorAvailable ? Http::response(['resources' => [['kind' => 'Cluster'], ['kind' => 'RedisCluster'], ['kind' => 'ValkeyCluster']]]) : Http::response([], 404);
        }
        if (str_contains($path, '/statefulsets/app-')) {
            return Http::response(['metadata' => ['generation' => 2, 'ownerReferences' => [['uid' => 'cluster-uid']]],
                'spec' => ['replicas' => 3, 'template' => ['spec' => ['containers' => [['image' => $this->image]]]]],
                'status' => ['observedGeneration' => $this->stale ? 1 : 2, 'readyReplicas' => 3, 'updatedReplicas' => 3, 'currentRevision' => 'revision-2', 'updateRevision' => 'revision-2']]);
        }
        if ($request->method() === 'GET') {
            return isset($this->remote[$path]) ? Http::response($this->remote[$path]) : Http::response([], 404);
        }
        $body = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);
        if ($body['kind'] === 'Namespace' && $request->method() === 'POST') {
            $path .= '/'.$body['metadata']['name'];
        }
        if (in_array($body['kind'], ['Cluster', 'RedisCluster', 'ValkeyCluster'], true)) {
            $body['metadata']['generation'] = 2;
            $body['metadata']['uid'] = 'cluster-uid';
            $conditions = array_map(fn (string $type): array => ['type' => $type, 'status' => $this->ready ? 'True' : 'False', 'observedGeneration' => $this->stale ? 1 : 2], ['Ready', 'ClusterFormed', 'SlotsAssigned']);
            $body['status'] = ['conditions' => $conditions, 'instances' => 3, 'readyInstances' => $this->ready ? 3 : 2,
                'currentPrimary' => 'app-1', 'state' => $this->ready ? 'Ready' : 'Bootstrap', 'readyShards' => 3,
                'readyLeaderReplicas' => 3, 'readyFollowerReplicas' => 3];
        }
        if (str_contains($request->url(), 'dryRun=All')) {
            return Http::response($body);
        }
        $this->remote[$path] = $body;

        return Http::response($body, $request->method() === 'POST' ? 201 : 200);
    });
    Cache::swap(new Repository(new ArrayStore()));
    config(['cache.default' => 'redis', 'hosting-kubernetes.data_sources.fixture' => [
        'team_id' => 'team-a', 'database_id' => $this->database->getKey(), 'cluster_id' => $this->cluster->getKey(), 'connection' => 'fixture', 'settings' => $this->settings,
    ], 'hosting-kubernetes.connections.fixture' => $this->connection]);
});

function deployDataFixture(object $test, string $driver, bool $dryRun = false): array
{
    return app(KubernetesProvisioner::class)->deployDataCluster('team-a', $test->database->getKey(), $driver, $test->settings, $test->connection, $dryRun);
}

it('applies a persistent operator-managed database cluster with verified readiness', function (string $driver): void {
    $result = deployDataFixture($this, $driver);
    expect($result['ready'])->toBeTrue()->and($result['driver'])->toBe($driver);
    $cluster = collect($this->remote)->first(fn (array $resource): bool => in_array($resource['kind'], ['Cluster', 'RedisCluster', 'ValkeyCluster'], true));
    if ($driver === 'postgresql') {
        expect($cluster['spec']['instances'])->toBe(3)
            ->and($cluster['spec']['postgresql']['synchronous']['dataDurability'])->toBe('required')
            ->and($cluster['spec']['affinity']['podAntiAffinityType'])->toBe('required');
    } elseif ($driver === 'valkey') {
        expect($cluster['spec']['shards'])->toBe(3)->and($cluster['spec']['replicas'])->toBe(1)
            ->and($cluster['spec']['persistence']['reclaimPolicy'])->toBe('Retain')
            ->and($cluster['spec']['networking']['discovery']['preferredEndpointType'])->toBe('Hostname');
    } else {
        expect($cluster['spec']['clusterSize'])->toBe(3)
            ->and($cluster['spec']['redisLeader']['replicas'])->toBe(3)
            ->and($cluster['spec']['redisFollower']['replicas'])->toBe(3)
            ->and($cluster['spec']['storage']['keepAfterDelete'])->toBeTrue();
    }
    expect(json_encode($result))->not->toContain('private-password', 'private-token', 'PRIVATE KEY');
})->with(['postgresql', 'redis', 'valkey']);

it('performs operator and ownership checks without mutation in dry-run mode', function (string $driver): void {
    $result = deployDataFixture($this, $driver, true);
    expect($result['dry_run'])->toBeTrue()->and($this->remote)->toBe([]);
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
})->with(['postgresql', 'redis', 'valkey']);

it('rejects a missing database operator before creating a namespace', function (): void {
    $this->operatorAvailable = false;
    expect(fn () => deployDataFixture($this, 'valkey'))->toThrow(RuntimeException::class, 'operator API');
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('does not report bootstrap or stale operator status as cluster readiness', function (string $driver, bool $stale): void {
    $this->stale = $stale;
    $this->ready = $stale;
    expect(deployDataFixture($this, $driver)['ready'])->toBeFalse();
})->with([['postgresql', false], ['postgresql', true], ['redis', false], ['redis', true], ['valkey', false], ['valkey', true]]);

it('refuses to overwrite existing database credentials before changing anything', function (): void {
    $plan = app(DataClusterPlan::class)->build('team-a', $this->database->getKey(), 'postgresql', $this->settings);
    $secret = collect($plan)->first(fn (array $resource): bool => $resource['body']['kind'] === 'Secret');
    $secret['body']['data']['password'] = base64_encode('existing-password');
    $this->remote[$secret['path']] = $secret['body'];
    expect(fn () => deployDataFixture($this, 'postgresql'))->toThrow(RuntimeException::class, 'Rotate credentials explicitly');
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('requires valid TLS material for Redis and Valkey clusters', function (string $driver): void {
    $this->settings['tls_key'] = 'not-a-private-key';
    expect(fn () => deployDataFixture($this, $driver))->toThrow(InvalidArgumentException::class, 'TLS material');
    Http::assertNothingSent();
})->with(['redis', 'valkey']);

it('records a sanitized data-cluster operator result through the actual command', function (): void {
    $this->artisan('hosting:deploy-data-cluster fixture')->assertSuccessful();
    $audit = AuditEntry::query()->where('event', 'hosting.data-cluster.provisioned')->firstOrFail();
    expect($audit->team_id)->toBe('team-a')->and($audit->context['ready'])->toBeTrue()
        ->and(json_encode($audit))->not->toContain('private-password', 'private-token');
});

it('rejects a database source bound to another tenant before contacting Kubernetes', function (): void {
    config(['hosting-kubernetes.data_sources.fixture.team_id' => 'team-b']);
    $this->artisan('hosting:deploy-data-cluster fixture')->assertFailed();
    Http::assertNothingSent();
});
