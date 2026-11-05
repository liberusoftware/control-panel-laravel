<?php

use App\Console\Commands\PublishHostingDns;
use Illuminate\Cache\ArrayStore;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Facade;
use Liberu\ControlPanel\Dns\DnsServiceProvider;
use Liberu\ControlPanel\Dns\Models\DnsCheck;
use Liberu\ControlPanel\Dns\Models\Zone;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->previousContainer = Container::getInstance();
    $this->previousFacadeApp = Facade::getFacadeApplication();
    $this->application = new Application(dirname(__DIR__));
    $this->configuration = new Repository([
        'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]],
        'cache' => ['default' => 'redis'],
    ]);
    $this->application->instance('config', $this->configuration);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->application);
    $this->application->register(DatabaseServiceProvider::class)->boot();
    $this->application->register(DnsServiceProvider::class);
    foreach (glob(dirname(__DIR__).'/modules/control-panel-dns/database/migrations/*.php') as $migration) {
        (require $migration)->up();
    }
    $this->locks = new ArrayStore();
    $this->application->instance('cache', $this->locks);
    $this->zone = Zone::query()->create(['team_id' => 'team-a', 'domain' => 'example.com', 'status' => 'draft', 'dnssec_enabled' => false]);
    foreach (['ns1.example.net', 'ns2.example.net'] as $nameserver) {
        $this->zone->records()->create(['name' => '@', 'type' => 'NS', 'content' => $nameserver, 'ttl' => 3600]);
    }
    $this->configuration->set('hosting-dns', [
        'sources' => ['fixture' => ['team_id' => 'team-a', 'zone_id' => $this->zone->getKey(), 'domain' => 'example.com', 'cluster' => 'test']],
        'clusters' => ['test' => [
            'ns1' => ['url' => 'https://ns1.example.net', 'api_key' => 'private-key-1'],
            'ns2' => ['url' => 'https://ns2.example.net', 'api_key' => 'private-key-2'],
        ]],
    ]);
    $this->http = new Factory();
    $this->http->preventStrayRequests();
    $this->http->fake(['*' => Factory::response([], 404)]);
    $this->application->instance(Factory::class, $this->http);
    $command = new PublishHostingDns();
    $command->setLaravel($this->application);
    $this->tester = new CommandTester($command);
});

afterEach(function (): void {
    $this->application['db']->disconnect();
    Model::unsetConnectionResolver();
    Model::unsetEventDispatcher();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousFacadeApp);
    Container::setInstance($this->previousContainer);
});

it('runs the actual command in dry-run mode and persists tenant-scoped preflight evidence', function (): void {
    expect($this->tester->execute(['source' => 'fixture', '--dry-run' => true]))->toBe(0);
    $check = DnsCheck::query()->firstOrFail();
    expect($check->team_id)->toBe('team-a')
        ->and($check->zone_id)->toBe($this->zone->getKey())
        ->and($check->kind)->toBe('validation')
        ->and($check->result['operation'])->toBe('preflight')
        ->and($check->status)->toBe('passed')
        ->and($check->result['verified'])->toBeFalse()
        ->and($this->tester->getDisplay())->toContain('No DNS records were changed.')
        ->and(json_encode($check))->not->toContain('private-key');
    $this->http->assertSentCount(2);
    $this->http->assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
});

it('rejects a source bound to another team or a different domain before making requests', function (string $field, string $value): void {
    $this->configuration->set('hosting-dns.sources.fixture.'.$field, $value);
    expect($this->tester->execute(['source' => 'fixture']))->toBe(1)
        ->and(DnsCheck::query()->count())->toBe(0);
    $this->http->assertNothingSent();
})->with([['team_id', 'team-b'], ['domain', 'foreign.example.net']]);

it('requires distributed Redis locking rather than accepting a local cache configuration', function (): void {
    $this->configuration->set('cache.default', 'file');
    expect($this->tester->execute(['source' => 'fixture']))->toBe(1)
        ->and($this->tester->getDisplay())->toContain('shared Redis locking');
    $this->http->assertNothingSent();
});

it('does not publish while another process owns the zone lock', function (): void {
    $lock = $this->locks->lock('hosting:dns:'.hash('sha256', 'example.com.'), 600);
    $lock->get();
    expect($this->tester->execute(['source' => 'fixture']))->toBe(1)
        ->and($this->tester->getDisplay())->toContain('already being published')
        ->and($lock->isOwnedByCurrentProcess())->toBeTrue();
    $this->http->assertNothingSent();
});

it('refuses signed suspended and archived inventory zones', function (array $attributes): void {
    $this->zone->update($attributes);
    expect($this->tester->execute(['source' => 'fixture']))->toBe(1);
    $this->http->assertNothingSent();
})->with([[['dnssec_enabled' => true]], [['status' => 'suspended']], [['status' => 'archived']]]);

it('records publication success only when every node matches unchanged inventory', function (bool $changeInventory): void {
    $remote = [];
    $published = [];
    $this->http = new Factory();
    $this->http->preventStrayRequests();
    $this->application->instance(Factory::class, $this->http);
    $this->http->fake(function (Request $request) use (&$remote, &$published, $changeInventory) {
        $node = parse_url($request->url(), PHP_URL_HOST);
        if ($request->method() === 'GET') {
            if ($changeInventory && $node === 'ns2.example.net' && isset($published[$node])) {
                $this->zone->records()->firstOrFail()->update(['ttl' => 1800]);
            }

            return isset($remote[$node]) ? Factory::response($remote[$node]) : Factory::response([], 404);
        }
        if ($request->method() === 'POST') {
            $remote[$node] = [...$request->data(), 'dnssec' => false, 'rrsets' => []];

            return Factory::response($remote[$node], 201);
        }
        $remote[$node]['rrsets'] = array_map(function (array $set): array {
            unset($set['changetype']);

            return $set;
        }, $request['rrsets']);
        $published[$node] = true;

        return Factory::response('', 204);
    });
    expect($this->tester->execute(['source' => 'fixture']))->toBe($changeInventory ? 1 : 0);
    $check = DnsCheck::query()->firstOrFail();
    expect($check->status)->toBe($changeInventory ? 'failed' : 'passed')
        ->and($check->result['verified'])->toBe(! $changeInventory)
        ->and($check->result['operation'])->toBe('publication')
        ->and($check->result['inventory_unchanged'])->toBe(! $changeInventory);
    $this->http->assertSentCount(10);
})->with([false, true]);

it('explains inventory validation failures without contacting any DNS node', function (): void {
    $this->zone->records()->firstOrFail()->delete();
    expect($this->tester->execute(['source' => 'fixture']))->toBe(1)
        ->and($this->tester->getDisplay())->toContain('At least two distinct apex nameservers')
        ->and(DnsCheck::query()->count())->toBe(0);
    $this->http->assertNothingSent();
});
