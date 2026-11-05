<?php

use App\Hosting\Dns\PowerDnsCluster;
use App\Hosting\Dns\PowerDnsZone;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;

beforeEach(function (): void {
    $this->compiler = new PowerDnsZone();
    $this->owner = 'liberu-'.str_repeat('a', 32);
    $this->records = [
        ['name' => '@', 'type' => 'NS', 'content' => 'ns1.example.net', 'ttl' => 3600],
        ['name' => '@', 'type' => 'NS', 'content' => 'ns2.example.net.', 'ttl' => 3600],
        ['name' => '@', 'type' => 'A', 'content' => '192.0.2.10', 'ttl' => 300],
    ];
    $this->nodes = [
        'ns1' => ['url' => 'https://ns1.example.net', 'api_key' => 'first-private-key'],
        'ns2' => ['url' => 'https://ns2.example.net', 'api_key' => 'second-private-key'],
    ];
    $this->remote = [];
    $this->failWrite = [];
    $this->stale = [];
    $this->offline = [];
    foreach (['ns1', 'ns2'] as $alias) {
        $this->remote[$alias] = [
            'name' => 'example.com.', 'kind' => 'Native', 'account' => $this->owner,
            'dnssec' => false, 'rrsets' => $this->compiler->rrsets('example.com', $this->records),
        ];
    }
    $this->http = new Factory();
    $this->http->preventStrayRequests();
    $this->http->fake(function (Request $request) {
        $alias = explode('.', parse_url($request->url(), PHP_URL_HOST))[0];
        if (in_array($alias, $this->offline, true)) {
            return Factory::response(['error' => 'upstream private-key'], 503);
        }
        if ($request->method() === 'GET') {
            return $this->remote[$alias] === null ? Factory::response([], 404) : Factory::response($this->remote[$alias]);
        }
        if ($request->method() === 'POST') {
            $this->remote[$alias] = [...$request->data(), 'dnssec' => false, 'rrsets' => []];

            return Factory::response($this->remote[$alias], 201);
        }
        if (in_array($alias, $this->failWrite, true)) {
            return Factory::response(['error' => 'private tenant data and private-key'], 500);
        }
        if (! in_array($alias, $this->stale, true)) {
            $sets = [];
            foreach ($this->remote[$alias]['rrsets'] as $set) {
                $sets[$set['name'].'|'.$set['type']] = $set;
            }
            foreach ($request['rrsets'] as $set) {
                $key = $set['name'].'|'.$set['type'];
                if ($set['changetype'] === 'DELETE') {
                    unset($sets[$key]);
                } else {
                    unset($set['changetype']);
                    $sets[$key] = $set;
                }
            }
            $this->remote[$alias]['rrsets'] = array_values($sets);
        }

        return Factory::response('', 204);
    });
    $this->cluster = new PowerDnsCluster($this->http, $this->compiler);
});

it('publishes and reads back desired records on every node', function (): void {
    $this->records[] = ['name' => 'mail', 'type' => 'A', 'content' => '192.0.2.20', 'ttl' => 300];
    $result = $this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes);

    expect($result['verified'])->toBeTrue()
        ->and($result['nodes'])->toBe(['ns1' => ['status' => 'verified'], 'ns2' => ['status' => 'verified']]);
    $this->http->assertSentCount(6);
    $this->http->assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
        && $request->hasHeader('X-API-Key', 'first-private-key')
        && str_contains($request->url(), '/api/v1/servers/localhost/zones/example.com.'));
});

it('creates missing zones with stable ownership and supports reruns', function (): void {
    $this->remote['ns2'] = null;
    expect($this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes)['verified'])->toBeTrue();
    expect($this->remote['ns2']['account'])->toBe($this->owner);
    expect($this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes)['verified'])->toBeTrue();
    $this->http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request['nameservers'] === ['ns1.example.net.', 'ns2.example.net.']);
});

it('removes stale managed RRsets while preserving the server SOA', function (): void {
    $soa = ['name' => 'example.com.', 'type' => 'SOA', 'ttl' => 3600, 'records' => [['content' => 'server generated SOA', 'disabled' => false]]];
    $this->remote['ns1']['rrsets'][] = $soa;
    $this->remote['ns1']['rrsets'][] = ['name' => 'obsolete.example.com.', 'type' => 'A', 'ttl' => 300, 'records' => [['content' => '192.0.2.99', 'disabled' => false]]];
    $result = $this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes);

    expect($result['verified'])->toBeTrue()
        ->and($this->remote['ns1']['rrsets'])->toContain($soa);
    $this->http->assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
        && in_array(['name' => 'obsolete.example.com.', 'type' => 'A', 'changetype' => 'DELETE'], $request['rrsets'], true));
});

it('performs no writes if any node is unreachable or owned by another installation', function (string $failure): void {
    if ($failure === 'offline') {
        $this->offline = ['ns2'];
    } else {
        $this->remote['ns2']['account'] = 'another-installation';
    }
    $result = $this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes);

    expect($result['verified'])->toBeFalse()
        ->and($result['nodes']['ns2']['status'])->toBe('preflight-failed');
    $this->http->assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
})->with(['offline', 'foreign-owner']);

it('rejects signed and non-Native zones before any mutation', function (string $field, mixed $value): void {
    $this->remote['ns2'][$field] = $value;
    expect($this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes)['verified'])->toBeFalse();
    $this->http->assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
})->with([['dnssec', true], ['kind', 'Secondary']]);

it('preflights every node in dry-run mode without changing records', function (): void {
    $before = $this->remote;
    $result = $this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes, true);

    expect($result['verified'])->toBeFalse()->and($result['dry_run'])->toBeTrue()->and($this->remote)->toBe($before);
    $this->http->assertSentCount(2);
});

it('reports partial failure and reconciles the failed node on rerun without leaking upstream errors', function (): void {
    $this->failWrite = ['ns2'];
    $result = $this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes);

    expect($result['verified'])->toBeFalse()
        ->and($result['nodes']['ns1']['status'])->toBe('verified')
        ->and($result['nodes']['ns2']['status'])->toBe('publication-failed')
        ->and(json_encode($result))->not->toContain('private-key', 'private tenant data');
    $this->failWrite = [];
    expect($this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes)['verified'])->toBeTrue();
});

it('does not trust a successful write response when read-back is stale', function (): void {
    $this->records[] = ['name' => 'new', 'type' => 'A', 'content' => '192.0.2.30', 'ttl' => 300];
    $this->stale = ['ns2'];
    expect($this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes)['verified'])->toBeFalse();
});

it('rejects unsafe cluster configuration before making network calls', function (string $change): void {
    match ($change) {
        'plaintext' => $this->nodes['ns1']['url'] = 'http://ns1.example.net',
        'redirect-query' => $this->nodes['ns1']['url'] = 'https://ns1.example.net?target=evil',
        'userinfo' => $this->nodes['ns1']['url'] = 'https://user:password@ns1.example.net',
        'duplicate' => $this->nodes['ns2']['url'] = $this->nodes['ns1']['url'],
        'header-injection' => $this->nodes['ns1']['api_key'] = "key\r\nOther: value",
        'single-node' => array_pop($this->nodes),
    };
    expect(fn () => $this->cluster->synchronize('example.com', $this->owner, $this->records, $this->nodes))->toThrow(InvalidArgumentException::class);
    $this->http->assertNothingSent();
})->with(['plaintext', 'redirect-query', 'userinfo', 'duplicate', 'header-injection', 'single-node']);

it('compiles mail and certificate records into canonical PowerDNS RRsets', function (): void {
    $sets = $this->compiler->rrsets('EXAMPLE.COM.', [...$this->records,
        ['name' => '@', 'type' => 'MX', 'content' => 'mail.example.com', 'priority' => 10, 'ttl' => 3600],
        ['name' => '_acme-challenge', 'type' => 'TXT', 'content' => 'test"quoted\\value', 'ttl' => 60],
        ['name' => '_sip._tcp', 'type' => 'SRV', 'content' => '0 5 5060 sip.example.com', 'ttl' => 3600],
        ['name' => '@', 'type' => 'CAA', 'content' => '0 issue "letsencrypt.org"', 'ttl' => 3600],
    ]);
    $contents = array_merge(...array_column($sets, 'records'));
    expect(array_column($contents, 'content'))->toContain('10 mail.example.com.', '"test\\"quoted\\\\value"', '0 5 5060 sip.example.com.', '0 issue "letsencrypt.org"');
});

it('rejects unsafe or inconsistent DNS records before touching any node', function (array $record): void {
    expect(fn () => $this->cluster->synchronize('example.com', $this->owner, [...$this->records, $record], $this->nodes))->toThrow(InvalidArgumentException::class);
    $this->http->assertNothingSent();
})->with([
    [['name' => 'foreign.example.net.', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => 60]],
    [['name' => '@', 'type' => 'A', 'content' => 'not-an-ip', 'ttl' => 300]],
    [['name' => '@', 'type' => 'A', 'content' => '192.0.2.2', 'ttl' => 600]],
    [['name' => '@', 'type' => 'CNAME', 'content' => 'target.example.net', 'ttl' => 300]],
    [['name' => '@', 'type' => 'TXT', 'content' => "line\nbreak", 'ttl' => 300]],
    [['name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.net', 'priority' => 65536, 'ttl' => 300]],
]);
