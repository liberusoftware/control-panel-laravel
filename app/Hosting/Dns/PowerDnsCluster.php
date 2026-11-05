<?php

namespace App\Hosting\Dns;

use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class PowerDnsCluster
{
    public function __construct(private Factory $http, private PowerDnsZone $zones) {}

    /**
     * @param iterable<array<string, mixed>> $records
     * @param array<string, array{url: string, api_key: string, server_id?: string}> $nodes
     * @return array{verified: bool, dry_run: bool, nodes: array<string, array{status: string}>}
     */
    public function synchronize(string $domain, string $owner, iterable $records, array $nodes, bool $dryRun = false): array
    {
        $domain = $this->zones->domain($domain);
        $desired = $this->zones->rrsets($domain, $records);
        if (! preg_match('/^liberu-[a-f0-9]{32}$/D', $owner) || count($nodes) < 2 || count($nodes) > 8) {
            throw new InvalidArgumentException('A stable owner marker and two to eight PowerDNS nodes are required.');
        }
        $clients = [];
        $urls = [];
        foreach ($nodes as $alias => $node) {
            $url = is_string($node['url'] ?? null) ? rtrim($node['url'], '/') : '';
            $parts = parse_url($url);
            if (! is_string($alias) || ! preg_match('/^[a-z0-9-]{1,63}$/D', $alias)
                || ! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])
                || isset($parts['user'], $parts['pass']) || isset($parts['user']) || isset($parts['query']) || isset($parts['fragment'])
                || preg_match('/[\x00-\x20\x7f]/', $url) || in_array(strtolower($url), $urls, true)
                || ! is_string($node['api_key'] ?? null) || $node['api_key'] === '' || preg_match('/[\r\n]/', $node['api_key'])) {
                throw new InvalidArgumentException('Configure distinct HTTPS node endpoints and private API keys.');
            }
            $urls[] = strtolower($url);
            $serverId = $node['server_id'] ?? 'localhost';
            if (! is_string($serverId) || ! preg_match('/^[a-zA-Z0-9_-]{1,63}$/D', $serverId)) {
                throw new InvalidArgumentException('Invalid PowerDNS server ID.');
            }
            $clients[$alias] = $this->http->baseUrl($url.'/api/v1/servers/'.$serverId)
                ->withHeaders(['X-API-Key' => $node['api_key']])->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)->withoutRedirecting();
        }
        $states = [];
        $results = [];
        $path = '/zones/'.rawurlencode($domain);
        foreach ($clients as $alias => $client) {
            try {
                $response = $client->get($path);
                if ($response->status() === 404) {
                    $states[$alias] = null;
                    $results[$alias] = ['status' => 'ready-to-create'];

                    continue;
                }
                $state = $response->successful() ? $response->json() : null;
                $this->assertManaged($state, $domain, $owner);
                $states[$alias] = $state;
                $results[$alias] = ['status' => 'ready-to-update'];
            } catch (Throwable) {
                $results[$alias] = ['status' => 'preflight-failed'];
            }
        }
        // Every node must pass ownership and connectivity checks before any write.
        if (count($states) !== count($clients) || $dryRun) {
            return ['verified' => false, 'dry_run' => $dryRun, 'nodes' => $results];
        }
        foreach ($clients as $alias => $client) {
            try {
                $state = $states[$alias];
                if ($state === null) {
                    $nameservers = array_values(array_filter($desired, fn (array $set): bool => $set['name'] === $domain && $set['type'] === 'NS'))[0];
                    $response = $client->post('/zones', [
                        'name' => $domain, 'kind' => 'Native', 'account' => $owner,
                        'nameservers' => array_column($nameservers['records'], 'content'),
                    ]);
                    if ($response->status() !== 201) {
                        throw new RuntimeException('Zone creation failed.');
                    }
                    $state = $client->get($path)->json();
                    $this->assertManaged($state, $domain, $owner);
                }
                $patch = array_map(fn (array $set): array => [...$set, 'changetype' => 'REPLACE'], $desired);
                $desiredKeys = array_map(fn (array $set): string => $set['name'].'|'.$set['type'], $desired);
                foreach ($state['rrsets'] as $set) {
                    if ($set['type'] !== 'SOA' && ! in_array(strtolower($set['name']).'|'.$set['type'], $desiredKeys, true)) {
                        $patch[] = ['name' => $set['name'], 'type' => $set['type'], 'changetype' => 'DELETE'];
                    }
                }
                if ($client->patch($path, ['rrsets' => $patch])->status() !== 204) {
                    throw new RuntimeException('RRset publication failed.');
                }
                $response = $client->get($path);
                $observed = $response->successful() ? $response->json() : null;
                $this->assertManaged($observed, $domain, $owner);
                if ($this->canonical($observed['rrsets']) !== $this->canonical($desired)) {
                    throw new RuntimeException('DNS read-back did not match desired records.');
                }
                $results[$alias] = ['status' => 'verified'];
            } catch (Throwable) {
                // Provider response bodies and exceptions may contain credentials or tenant data.
                $results[$alias] = ['status' => 'publication-failed'];
            }
        }

        return ['verified' => count(array_filter($results, fn (array $result): bool => $result['status'] === 'verified')) === count($clients), 'dry_run' => false, 'nodes' => $results];
    }

    private function assertManaged(mixed $state, string $domain, string $owner): void
    {
        if (! is_array($state) || ($state['name'] ?? null) !== $domain || ($state['kind'] ?? null) !== 'Native'
            || ($state['account'] ?? null) !== $owner || ($state['dnssec'] ?? true) !== false || ! is_array($state['rrsets'] ?? null)) {
            throw new RuntimeException('The remote zone is not an unsigned Native zone owned by this installation.');
        }
    }

    /** @param list<array<string, mixed>> $sets */
    private function canonical(array $sets): string
    {
        $normalized = [];
        foreach ($sets as $set) {
            if (($set['type'] ?? null) === 'SOA') {
                continue;
            }
            $records = array_map(fn (array $record): array => ['content' => $record['content'], 'disabled' => $record['disabled']], $set['records']);
            usort($records, fn (array $left, array $right): int => strcmp($left['content'], $right['content']));
            $normalized[strtolower($set['name']).'|'.$set['type']] = ['ttl' => $set['ttl'], 'records' => $records];
        }
        ksort($normalized);

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }
}
