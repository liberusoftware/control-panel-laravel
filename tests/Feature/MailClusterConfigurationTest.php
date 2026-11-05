<?php

use App\Hosting\Mail\StalwartCluster;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Liberu\ControlPanel\ControlCore\ControlCoreServiceProvider;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;
use Liberu\ControlPanel\Mail\MailServiceProvider;
use Liberu\ControlPanel\Mail\Models\MailDomain;

beforeEach(function (): void {
    app()->register(MailServiceProvider::class);
    app()->register(ControlCoreServiceProvider::class);
    $this->artisan('migrate');
    $this->domain = MailDomain::query()->create(['team_id' => 'team-a', 'domain' => 'example.test', 'status' => 'active']);
    $this->nodes = ['mail1' => ['url' => 'https://mail1.example.test', 'token' => 'private-mail1-token'], 'mail2' => ['url' => 'https://mail2.example.test', 'token' => 'private-mail2-token']];
    $this->dataStore = ['@type' => 'PostgreSql', 'host' => 'postgres.example.test', 'port' => 5432, 'database' => 'mail', 'authUsername' => 'mail', 'useTls' => true, 'allowInvalidCerts' => false];
    $this->memoryStore = ['@type' => 'RedisCluster', 'urls' => ['rediss://cache1.example.test:6379' => true, 'rediss://cache2.example.test:6379' => true], 'allowInvalidCerts' => false, 'authUsername' => 'default', 'authSecret' => ['@type' => 'EnvironmentVariable', 'variableName' => 'STALWART_REDIS_PASSWORD']];
    $this->shared = ['DataStore' => $this->dataStore, 'BlobStore' => ['@type' => 'Default'], 'SearchStore' => ['@type' => 'Default'], 'InMemoryStore' => ['@type' => 'Default'], 'Coordinator' => ['@type' => 'Disabled']];
    $this->offline = false;
    $this->divergentStore = false;
    $this->stateMismatch = false;
    $this->unhealthy = false;
    $this->version = 1;
    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $second = str_contains($request->url(), 'mail2.');
        if ($second && $this->offline) {
            return Http::response(['error' => 'private-password'], 503);
        }
        if (str_ends_with($request->url(), '/healthz/ready')) {
            return Http::response('', $second && $this->unhealthy ? 503 : 200);
        }
        $replies = [];
        foreach ($request['methodCalls'] as [$method, $arguments, $id]) {
            preg_match('~^x:(.+)/(get|set)$~', $method, $matches);
            $object = $matches[1];
            if ($matches[2] === 'get') {
                $value = $this->shared[$object];
                if ($second && $this->divergentStore && $object === 'DataStore') {
                    $value['database'] = 'foreign-mailstore';
                }
                $replies[] = [$method, ['state' => (string) $this->version, 'list' => [['id' => 'singleton', ...$value]], 'notFound' => []], $id];
            } elseif ($object === 'Action') {
                $replies[] = [$method, ['created' => ['reload' => ['id' => 'reload-action']]], $id];
            } elseif ($this->stateMismatch) {
                $replies[] = ['error', ['type' => 'stateMismatch', 'description' => 'private upstream data'], $id];
            } else {
                $this->shared[$object] = $arguments['update']['singleton'];
                $this->version++;
                $replies[] = [$method, ['updated' => ['singleton' => null]], $id];
            }
        }

        return Http::response(['methodResponses' => $replies]);
    });
    Cache::swap(new Repository(new ArrayStore()));
    config(['cache.default' => 'redis', 'hosting-mail.sources.fixture' => ['team_id' => 'team-a', 'domain_id' => $this->domain->getKey(), 'domain' => 'example.test', 'nodes' => $this->nodes, 'data_store' => $this->dataStore, 'memory_store' => $this->memoryStore]]);
});

it('configures one shared memory store then coordination and verifies every mail node', function (): void {
    $result = app(StalwartCluster::class)->configure($this->nodes, $this->dataStore, $this->memoryStore);
    expect($result['verified'])->toBeTrue()
        ->and($this->shared['Coordinator'])->toBe(['@type' => 'Default'])
        ->and($this->shared['InMemoryStore'])->toBe($this->memoryStore)
        ->and(json_encode($result))->not->toContain('private-mail', 'STALWART_REDIS_PASSWORD');
    Http::assertNotSent(fn (Request $request): bool => collect($request['methodCalls'] ?? [])->contains(fn (array $call): bool => in_array($call[0], ['x:DataStore/set', 'x:BlobStore/set', 'x:SearchStore/set'], true)));
});

it('does not configure independent local or mismatched mail stores as a cluster', function (string $failure): void {
    match ($failure) {
        'offline' => $this->offline = true,
        'different-database' => $this->divergentStore = true,
        'filesystem-blobs' => $this->shared['BlobStore'] = ['@type' => 'Fs', 'path' => '/mail'],
    };
    expect(app(StalwartCluster::class)->configure($this->nodes, $this->dataStore, $this->memoryStore)['verified'])->toBeFalse();
    Http::assertNotSent(fn (Request $request): bool => collect($request['methodCalls'] ?? [])->contains(fn (array $call): bool => str_ends_with($call[0], '/set')));
})->with(['offline', 'different-database', 'filesystem-blobs']);

it('preflights all nodes without changing mail configuration during a dry run', function (): void {
    $before = $this->shared;
    $result = app(StalwartCluster::class)->configure($this->nodes, $this->dataStore, $this->memoryStore, true);
    expect($result['dry_run'])->toBeTrue()->and($this->shared)->toBe($before);
    Http::assertSentCount(2);
});

it('does not accept a JMAP HTTP success containing a state conflict as a successful update', function (): void {
    $this->stateMismatch = true;
    $result = app(StalwartCluster::class)->configure($this->nodes, $this->dataStore, $this->memoryStore);
    expect($result['verified'])->toBeFalse()->and(json_encode($result))->not->toContain('private upstream data');
});

it('requires node health as well as shared configuration before reporting verification', function (): void {
    $this->unhealthy = true;
    $result = app(StalwartCluster::class)->configure($this->nodes, $this->dataStore, $this->memoryStore);
    expect($result['verified'])->toBeFalse()->and($result['nodes']['mail2']['status'])->toBe('verification-failed');
});

it('rejects embedded datastores or plaintext in-memory cluster connections', function (string $failure): void {
    if ($failure === 'embedded') {
        $this->dataStore['@type'] = 'RocksDb';
    } else {
        $this->memoryStore['urls'] = ['redis://cache1.example.test' => true, 'redis://cache2.example.test' => true];
    }
    expect(fn () => app(StalwartCluster::class)->configure($this->nodes, $this->dataStore, $this->memoryStore))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with(['embedded', 'plaintext']);

it('writes sanitized team-scoped audit evidence through the mail cluster command', function (): void {
    $this->artisan('hosting:configure-mail-cluster fixture')->assertSuccessful();
    $entry = AuditEntry::query()->where('event', 'hosting.mail-cluster.configured')->firstOrFail();
    expect($entry->team_id)->toBe('team-a')->and($entry->context['verified'])->toBeTrue()
        ->and(json_encode($entry))->not->toContain('private-mail', 'STALWART_REDIS_PASSWORD');
});

it('refuses a foreign team source before contacting mail infrastructure', function (): void {
    config(['hosting-mail.sources.fixture.team_id' => 'team-b']);
    $this->artisan('hosting:configure-mail-cluster fixture')->assertFailed();
    Http::assertNothingSent();
});
