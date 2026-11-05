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
    $this->databaseImage = 'registry.example/mariadb@sha256:'.str_repeat('a', 64);
    $this->operatorImage = 'registry.example/mariadb-operator@sha256:'.str_repeat('b', 64);
    $this->settings = [
        'image' => $this->databaseImage, 'allowed_images' => [$this->databaseImage],
        'operator_image' => $this->operatorImage, 'allowed_operator_images' => [$this->operatorImage],
        'password' => str_repeat('application-password', 3), 'root_password' => str_repeat('administrator-password', 3),
        'database' => 'website', 'storage_class' => 'durable', 'storage_gi' => 10,
        'operator_namespace' => 'database-operators', 'api_server_ip' => '10.96.0.1',
        'client_namespaces' => ['tenant-approved'], 'network_policy_enforced' => true,
    ];
    $this->connection = ['url' => 'https://cluster.example.test', 'token' => 'private-cluster-token'];
    $this->remote = [];
    $this->statefulSetProblem = null;
    $this->operatorAvailable = true;
    $this->operatorStale = false;
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $path = parse_url($request->url(), PHP_URL_PATH);
        if ($path === '/apis/k8s.mariadb.com/v1alpha1') {
            return $this->operatorAvailable ? Http::response(['resources' => [['kind' => 'MariaDB']]]) : Http::response([], 404);
        }
        if (str_ends_with($path, '/statefulsets/app')) {
            $state = ['metadata' => ['generation' => 2, 'ownerReferences' => [['uid' => 'mariadb-uid']]],
                'spec' => ['replicas' => 3, 'template' => ['spec' => [
                    'containers' => [['image' => $this->databaseImage], ['image' => $this->operatorImage]],
                    'initContainers' => [['image' => $this->operatorImage]],
                ]]],
                'status' => ['observedGeneration' => 2, 'readyReplicas' => 3, 'updatedReplicas' => 3, 'currentRevision' => 'current', 'updateRevision' => 'current']];
            match ($this->statefulSetProblem) {
                'stale' => $state['status']['observedGeneration'] = 1,
                'updating' => $state['status']['updateRevision'] = 'new',
                'missing-replica' => $state['status']['readyReplicas'] = 2,
                'foreign-owner' => $state['metadata']['ownerReferences'][0]['uid'] = 'another-uid',
                'wrong-image' => $state['spec']['template']['spec']['containers'][0]['image'] = 'wrong',
                'missing-agent' => array_pop($state['spec']['template']['spec']['containers']),
                'wrong-init-image' => $state['spec']['template']['spec']['initContainers'][0]['image'] = 'wrong',
                default => null,
            };

            return Http::response($state);
        }
        if ($request->method() === 'GET') {
            return isset($this->remote[$path]) ? Http::response($this->remote[$path]) : Http::response([], 404);
        }
        $body = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);
        if ($body['kind'] === 'Namespace' && $request->method() === 'POST') {
            $path .= '/'.$body['metadata']['name'];
        }
        if ($body['kind'] === 'MariaDB') {
            $body['metadata'] += ['generation' => 2, 'uid' => 'mariadb-uid'];
            $body['status'] = ['replicas' => 3, 'currentPrimary' => 'app-1', 'conditions' => [['type' => 'Ready', 'status' => 'True', 'observedGeneration' => $this->operatorStale ? 1 : 2]]];
        }
        if (! str_contains($request->url(), 'dryRun=All')) {
            $this->remote[$path] = $body;
        }

        return Http::response($body, $request->method() === 'POST' ? 201 : 200);
    });
});

function provisionMariaDbFixture(object $test, bool $dryRun = false): array
{
    return app(KubernetesProvisioner::class)->deployDataCluster('team', 'database', 'mariadb', $test->settings, $test->connection, $dryRun);
}

it('plans a three-member TLS Galera cluster with a separate restricted application user', function (): void {
    $plan = app(DataClusterPlan::class)->build('team', 'database', 'mariadb', $this->settings);
    $cluster = collect($plan)->firstWhere('body.kind', 'MariaDB')['body'];
    expect($cluster['spec']['replicas'])->toBe(3)
        ->and($cluster['spec']['tls'])->toMatchArray(['enabled' => true, 'required' => true, 'galeraSSTEnabled' => true])
        ->and($cluster['spec']['galera']['primary']['autoFailover'])->toBeTrue()
        ->and($cluster['spec']['galera']['config']['reuseStorageVolume'])->toBeTrue()
        ->and($cluster['spec']['galera']['agent']['image'])->toBe($this->operatorImage)
        ->and($cluster['spec']['rootPasswordSecretKeyRef']['name'])->toBe('app-admin')
        ->and($cluster['spec']['passwordSecretKeyRef']['name'])->toBe('app')
        ->and($cluster['spec']['username'])->toBe('app')
        ->and($cluster['spec']['podDisruptionBudget']['maxUnavailable'])->toBe(1)
        ->and($cluster['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])->toHaveCount(1);
    $policy = collect($plan)->firstWhere('body.kind', 'NetworkPolicy')['body']['spec'];
    $clientRule = collect($policy['ingress'])->first(fn (array $rule): bool => ($rule['from'][0]['namespaceSelector']['matchLabels']['kubernetes.io/metadata.name'] ?? '') === 'tenant-approved');
    expect($clientRule['ports'])->toBe([['protocol' => 'TCP', 'port' => 3306]])
        ->and(collect($policy['ingress'])->contains(fn (array $rule): bool => in_array(['protocol' => 'UDP', 'port' => 4567], $rule['ports'], true)))->toBeTrue();
    $applicationSecret = collect($plan)->firstWhere('path', $plan[0]['path'].'/secrets/app')['body'];
    expect(array_keys($applicationSecret['data']))->toBe(['password']);
});

it('verifies the current-generation operator and every member image before reporting readiness', function (): void {
    $result = provisionMariaDbFixture($this);
    expect($result['ready'])->toBeTrue()->and(json_encode($result))->not->toContain('application-password', 'administrator-password', 'private-cluster-token');
});

it('fails readiness for incomplete, foreign, stale or altered member workloads', function (string $problem): void {
    $this->statefulSetProblem = $problem;
    expect(provisionMariaDbFixture($this)['ready'])->toBeFalse();
})->with(['stale', 'updating', 'missing-replica', 'foreign-owner', 'wrong-image', 'missing-agent', 'wrong-init-image']);

it('does not accept stale operator readiness', function (): void {
    $this->operatorStale = true;
    expect(provisionMariaDbFixture($this)['ready'])->toBeFalse();
});

it('never overwrites the database administrator secret during ordinary provisioning', function (): void {
    provisionMariaDbFixture($this);
    $before = $this->remote;
    $this->settings['root_password'] = str_repeat('changed-password', 4);
    expect(fn () => provisionMariaDbFixture($this))->toThrow(RuntimeException::class, 'credentials differ');
    expect($this->remote)->toBe($before);
});

it('refuses image upgrades without an explicitly reviewed database migration', function (): void {
    provisionMariaDbFixture($this);
    $before = $this->remote;
    $this->settings['image'] = 'registry.example/mariadb@sha256:'.str_repeat('c', 64);
    $this->settings['allowed_images'][] = $this->settings['image'];
    expect(fn () => provisionMariaDbFixture($this))->toThrow(RuntimeException::class, 'image upgrades');
    expect($this->remote)->toBe($before);
});

it('rejects missing or shared administrator credentials and unapproved helper images before API calls', function (string $problem): void {
    match ($problem) {
        'missing-password' => $this->settings['root_password'] = '',
        'shared-password' => $this->settings['root_password'] = $this->settings['password'],
        'mutable-helper' => $this->settings['operator_image'] = 'registry.example/operator:latest',
        'unapproved-helper' => $this->settings['allowed_operator_images'] = [],
    };
    expect(fn () => provisionMariaDbFixture($this))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with(['missing-password', 'shared-password', 'mutable-helper', 'unapproved-helper']);

it('checks the MariaDB operator before creating any resources', function (): void {
    $this->operatorAvailable = false;
    expect(fn () => provisionMariaDbFixture($this))->toThrow(RuntimeException::class, 'operator API');
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('supports a read-only MariaDB provisioning plan', function (): void {
    expect(provisionMariaDbFixture($this, true)['dry_run'])->toBeTrue()->and($this->remote)->toBe([]);
    Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('refuses implicit Galera helper-image upgrades', function (): void {
    provisionMariaDbFixture($this);
    $before = $this->remote;
    $this->settings['operator_image'] = 'registry.example/mariadb-operator@sha256:'.str_repeat('d', 64);
    $this->settings['allowed_operator_images'][] = $this->settings['operator_image'];
    expect(fn () => provisionMariaDbFixture($this))->toThrow(RuntimeException::class, 'image upgrades');
    expect($this->remote)->toBe($before);
});

it('binds MariaDB command provisioning and audit evidence to the owning tenant', function (bool $wrongTenant): void {
    foreach ([ControlCoreServiceProvider::class, DatabasesServiceProvider::class, KubernetesServiceProvider::class] as $provider) {
        app()->register($provider);
    }
    $this->artisan('migrate');
    $cluster = app(RegisterCluster::class)->execute(['team_id' => 'team', 'name' => 'Galera target', 'endpoint' => $this->connection['url']]);
    $engine = DatabaseEngine::query()->create(['team_id' => 'team', 'name' => 'MariaDB', 'driver' => 'mariadb', 'version' => '11.8', 'host' => 'pending', 'port' => 3306, 'active' => true]);
    $database = app(CreateDatabase::class)->execute(['team_id' => 'team', 'engine_id' => $engine->getKey(), 'name' => 'website']);
    config(['cache.default' => 'redis', 'hosting-kubernetes.connections.galera' => $this->connection,
        'hosting-kubernetes.data_sources.galera' => ['team_id' => $wrongTenant ? 'other-team' : 'team', 'database_id' => $database->getKey(),
            'cluster_id' => $cluster->getKey(), 'connection' => 'galera', 'settings' => $this->settings]]);
    Cache::swap(new Repository(new ArrayStore()));
    if ($wrongTenant) {
        $this->artisan('hosting:deploy-data-cluster galera')->assertFailed();
        Http::assertNothingSent();
    } else {
        $this->artisan('hosting:deploy-data-cluster galera')->assertSuccessful();
        $audit = AuditEntry::query()->where('event', 'hosting.data-cluster.provisioned')->firstOrFail();
        expect($audit->getAttribute('team_id'))->toBe('team')->and(json_encode($audit))->not->toContain('administrator-password', 'application-password', 'private-cluster-token');
    }
})->with([false, true]);
