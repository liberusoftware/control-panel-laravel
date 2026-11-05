<?php

namespace App\Hosting\Mail;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class StalwartCluster
{
    private const OBJECTS = ['DataStore', 'BlobStore', 'SearchStore', 'InMemoryStore', 'Coordinator'];

    public function __construct(private Factory $http) {}

    /**
     * @param  array<string, array{url: string, token: string}>  $nodes
     * @param  array<string, mixed>  $dataStore
     * @param  array<string, mixed>  $memoryStore
     * @return array{verified: bool, dry_run: bool, nodes: array<string, array{status: string}>}
     */
    public function configure(array $nodes, array $dataStore, array $memoryStore, bool $dryRun = false): array
    {
        if (count($nodes) < 2 || count($nodes) > 8 || ($dataStore['@type'] ?? '') !== 'PostgreSql'
            || empty($dataStore['host']) || empty($dataStore['database']) || empty($dataStore['authUsername'])
            || ($dataStore['useTls'] ?? false) !== true || ($dataStore['allowInvalidCerts'] ?? true) !== false) {
            throw new InvalidArgumentException('Mail clustering requires two to eight nodes sharing an explicitly allowlisted PostgreSQL store with verified TLS.');
        }
        $urls = $memoryStore['urls'] ?? [];
        if (($memoryStore['@type'] ?? '') !== 'RedisCluster' || ! is_array($urls) || count($urls) < 2
            || ($memoryStore['allowInvalidCerts'] ?? true) !== false || ($memoryStore['authSecret']['@type'] ?? '') !== 'EnvironmentVariable'
            || ! preg_match('/^[A-Z][A-Z0-9_]{0,127}$/D', $memoryStore['authSecret']['variableName'] ?? '')) {
            throw new InvalidArgumentException('Use a TLS Redis/Valkey Cluster in-memory store with at least two seeds and a node-local environment secret.');
        }
        foreach ($urls as $url => $enabled) {
            $parts = is_string($url) ? parse_url($url) : false;
            if ($enabled !== true || ! is_array($parts) || ($parts['scheme'] ?? '') !== 'rediss' || empty($parts['host'])
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                || ! in_array($parts['path'] ?? '', ['', '/', '/0'], true)) {
                throw new InvalidArgumentException('In-memory cluster URLs must use verified TLS, database zero and separate credentials.');
            }
        }
        $clients = [];
        $endpoints = [];
        foreach ($nodes as $name => $node) {
            $url = is_string($node['url'] ?? null) ? rtrim($node['url'], '/') : '';
            $parts = parse_url($url);
            if (! is_string($name) || ! preg_match('/^[a-z0-9-]{1,63}$/D', $name) || ! is_array($parts)
                || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['query']) || isset($parts['fragment']) || preg_match('/[\x00-\x20\x7f]/', $url)
                || in_array(strtolower($url), $endpoints, true) || ! is_string($node['token'] ?? null)
                || $node['token'] === '' || preg_match('/[\r\n]/', $node['token'])) {
                throw new InvalidArgumentException('Use distinct HTTPS mail-node origins and private bearer tokens.');
            }
            $endpoints[] = strtolower($url);
            $clients[$name] = $this->http->baseUrl($url)->withToken($node['token'])->asJson()->acceptJson()->connectTimeout(2)->timeout(5)->withoutRedirecting();
        }
        $states = [];
        $results = [];
        foreach ($clients as $name => $client) {
            try {
                $state = $this->read($client);
                $this->assertShared($state, $dataStore);
                $states[$name] = $state;
                $results[$name] = ['status' => 'preflight-passed'];
            } catch (Throwable) {
                $results[$name] = ['status' => 'preflight-failed'];
            }
        }
        if (count($states) !== count($clients) || $dryRun) {
            return ['verified' => false, 'dry_run' => $dryRun, 'nodes' => $results];
        }
        try {
            $leader = array_key_first($clients);
            $this->set($clients[$leader], 'InMemoryStore', $memoryStore, $states[$leader]['InMemoryStore']['state']);
            $current = $this->read($clients[$leader]);
            $this->assertShared($current, $dataStore);
            $this->set($clients[$leader], 'Coordinator', ['@type' => 'Default'], $current['Coordinator']['state']);
        } catch (Throwable) {
            return ['verified' => false, 'dry_run' => false, 'nodes' => array_fill_keys(array_keys($clients), ['status' => 'configuration-failed'])];
        }
        foreach ($clients as $name => $client) {
            try {
                $reload = $this->call($client, [['x:Action/set', ['create' => ['reload' => ['@type' => 'ReloadSettings']]], 'reload']]);
                $reply = $reload['methodResponses'][0] ?? [];
                if (($reply[0] ?? '') !== 'x:Action/set' || ! isset($reply[1]['created']['reload']) || ! empty($reply[1]['notCreated'])) {
                    throw new RuntimeException('Settings reload was rejected.');
                }
                $state = $this->read($client);
                $this->assertShared($state, $dataStore);
                $actualMemory = $state['InMemoryStore']['value'];
                foreach (['@type', 'allowInvalidCerts', 'authUsername'] as $field) {
                    if (($actualMemory[$field] ?? null) !== ($memoryStore[$field] ?? null)) {
                        throw new RuntimeException('The shared in-memory store does not match.');
                    }
                }
                $actualUrls = $actualMemory['urls'] ?? [];
                $desiredUrls = $memoryStore['urls'];
                ksort($actualUrls);
                ksort($desiredUrls);
                if ($actualUrls !== $desiredUrls || ($actualMemory['authSecret']['@type'] ?? '') !== 'EnvironmentVariable'
                    || ($actualMemory['authSecret']['variableName'] ?? '') !== $memoryStore['authSecret']['variableName']) {
                    throw new RuntimeException('The in-memory cluster seeds or credential reference do not match.');
                }
                if (($state['Coordinator']['value']['@type'] ?? '') !== 'Default' || $client->get('/healthz/ready')->status() !== 200) {
                    throw new RuntimeException('Mail cluster settings or node health could not be verified.');
                }
                $results[$name] = ['status' => 'configuration-verified'];
            } catch (Throwable) {
                $results[$name] = ['status' => 'verification-failed'];
            }
        }

        return ['verified' => count(array_filter($results, fn (array $node): bool => $node['status'] === 'configuration-verified')) === count($clients), 'dry_run' => false, 'nodes' => $results];
    }

    /** @return array<string, array{state: string, value: array<string, mixed>}> */
    private function read(PendingRequest $client): array
    {
        $calls = array_map(fn (string $object): array => ['x:'.$object.'/get', ['ids' => ['singleton']], $object], self::OBJECTS);
        $body = $this->call($client, $calls);
        $objects = [];
        if (count($body['methodResponses'] ?? []) !== count(self::OBJECTS)) {
            throw new RuntimeException('Incomplete mail node configuration.');
        }
        foreach ($body['methodResponses'] ?? [] as $reply) {
            [$method, $data, $id] = $reply;
            if (! in_array($id, self::OBJECTS, true) || $method !== 'x:'.$id.'/get' || ! is_string($data['state'] ?? null)
                || count($data['list'] ?? []) !== 1 || ($data['list'][0]['id'] ?? '') !== 'singleton' || ! empty($data['notFound'])) {
                throw new RuntimeException('The mail node did not return complete cluster configuration.');
            }
            $objects[$id] = ['state' => $data['state'], 'value' => $data['list'][0]];
        }
        if (count($objects) !== count(self::OBJECTS)) {
            throw new RuntimeException('Incomplete mail node configuration.');
        }

        return $objects;
    }

    /** @param array<string, mixed> $settings */
    private function set(PendingRequest $client, string $object, array $settings, string $state): void
    {
        $body = $this->call($client, [['x:'.$object.'/set', ['ifInState' => $state, 'update' => ['singleton' => $settings]], 'update']]);
        $reply = $body['methodResponses'][0] ?? [];
        if (($reply[0] ?? '') !== 'x:'.$object.'/set' || ! array_key_exists('singleton', $reply[1]['updated'] ?? []) || ! empty($reply[1]['notUpdated'])) {
            throw new RuntimeException('The shared configuration update was not accepted.');
        }
    }

    /** @param list<array<mixed>> $calls
     * @return array<string, mixed>
     */
    private function call(PendingRequest $client, array $calls): array
    {
        $response = $client->post('/jmap/', ['using' => ['urn:ietf:params:jmap:core', 'urn:stalwart:jmap'], 'methodCalls' => $calls]);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('The mail management API rejected the operation.');
        }

        return $response->json();
    }

    /** @param array<string, array{state: string, value: array<string, mixed>}> $state
     * @param  array<string, mixed>  $expected
     */
    private function assertShared(array $state, array $expected): void
    {
        foreach (['@type', 'host', 'port', 'database', 'authUsername', 'useTls', 'allowInvalidCerts'] as $field) {
            if (! array_key_exists($field, $expected) || ($state['DataStore']['value'][$field] ?? null) !== $expected[$field]) {
                throw new RuntimeException('Mail nodes do not share the allowlisted datastore. No data-store migration was attempted.');
            }
        }
        foreach (['BlobStore', 'SearchStore'] as $object) {
            if (($state[$object]['value']['@type'] ?? '') !== 'Default') {
                throw new RuntimeException('This mail adapter requires blobs and search to use the shared database.');
            }
        }
    }
}
