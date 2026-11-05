<?php

namespace App\Hosting\Migrations;

use Illuminate\Http\Client\Factory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class VirtualminSource
{
    public function __construct(private Factory $http) {}

    /**
     * Inspect only: source metadata is never treated as permission to import or cut over.
     *
     * @param  array<string, mixed>  $connection
     * @param  array<array-key, mixed>  $approvedDomains
     * @return array{provider: string, version: string, edition: string, domains: list<array<string, mixed>>, excluded_domains: int}
     */
    public function inspect(#[\SensitiveParameter] array $connection, array $approvedDomains): array
    {
        $url = $connection['url'] ?? '';
        $parts = is_string($url) ? parse_url($url) : false;
        $username = $connection['username'] ?? '';
        $password = $connection['password'] ?? '';
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || ! in_array($parts['path'] ?? '', ['', '/'], true) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || preg_match('/[\x00-\x20\x7f]/', $url)
            || ! is_string($username) || ! preg_match('/^[a-z_][a-z0-9_-]{0,63}$/Di', $username)
            || ! is_string($password) || $password === '' || strlen($password) > 4096 || preg_match('/[\x00-\x1f\x7f]/', $password)) {
            throw new InvalidArgumentException('Configure an approved HTTPS Virtualmin origin and private administrator credentials.');
        }
        if (! array_is_list($approvedDomains) || $approvedDomains === [] || count($approvedDomains) > 10000) {
            throw new InvalidArgumentException('Approve explicit source domains, or the single wildcard entry for an operator-owned whole-server migration.');
        }
        $allDomains = $approvedDomains === ['*'];
        $approved = [];
        if (! $allDomains) {
            foreach ($approvedDomains as $domain) {
                if (! is_string($domain)) {
                    throw new InvalidArgumentException('Source domain approvals must contain hostname strings only.');
                }
                $approved[] = $this->domain($domain);
            }
        }
        if (count($approved) !== count(array_unique($approved))) {
            throw new InvalidArgumentException('Source domain approvals must be unique.');
        }
        $client = $this->http->baseUrl(rtrim($url, '/'))->withBasicAuth($username, $password)
            ->asForm()->acceptJson()->connectTimeout(3)->timeout(20)->withoutRedirecting()
            ->withOptions(['progress' => static function (float $total, float $downloaded): void {
                if ($downloaded > 8 * 1024 * 1024) {
                    throw new RuntimeException('Source inventory exceeds the bounded response size.');
                }
            }]);
        if (filled($connection['ca_certificate'] ?? null)) {
            $ca = $connection['ca_certificate'];
            if (! is_string($ca) || ! is_file($ca) || ! is_readable($ca)) {
                throw new InvalidArgumentException('Configure a readable source CA certificate; TLS verification cannot be disabled.');
            }
            $client = $client->withOptions(['verify' => $ca]);
        }
        $responses = [];
        foreach (['info' => ['search' => 'host'], 'list-domains' => ['multiline' => '']] as $program => $parameters) {
            try {
                $response = $client->post('/virtual-server/remote.cgi', ['program' => $program, 'json' => '1', ...$parameters]);
            } catch (Throwable) {
                throw new RuntimeException('Source inventory connection failed. Verify source TLS, authentication and connectivity.');
            }
            if (! $response->successful() || strlen($response->body()) > 8 * 1024 * 1024
                || $response->json('status') !== 'success' || $response->json('command') !== $program) {
                throw new RuntimeException('The source rejected or returned an invalid inventory response. No credentials or upstream error text were printed.');
            }
            $responses[$program] = $response->json();
        }
        $host = $responses['info']['output'] ?? '';
        if (! is_string($host) || ! preg_match('/^\s*virtualmin version:\s*(8\.[0-9]+(?:\.[0-9]+)?(?:[.a-z0-9+-]*))\s*$/mi', $host, $version)
            || ! preg_match('/^\s*virtualmin edition:\s*(GPL|Pro|Professional)\s*$/mi', $host, $edition)) {
            throw new RuntimeException('The inventory adapter requires a recognized Virtualmin 8 GPL or Pro source.');
        }
        $rows = $responses['list-domains']['data'] ?? null;
        if (! is_array($rows) || ! array_is_list($rows) || count($rows) > 10000) {
            throw new RuntimeException('The source domain inventory is malformed or too large.');
        }
        $domains = [];
        $seen = [];
        $sourceIds = [];
        $excluded = 0;
        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['name'] ?? null) || ! is_array($row['values'] ?? null)) {
                throw new RuntimeException('The source returned malformed domain metadata.');
            }
            $domain = $this->domain($row['name']);
            if (isset($seen[$domain])) {
                throw new RuntimeException('The source returned duplicate domain identities.');
            }
            $seen[$domain] = true;
            if (! $allDomains && ! in_array($domain, $approved, true)) {
                $excluded++;

                continue;
            }
            $fields = $row['values'];
            $id = $this->scalar($fields, 'id');
            $user = $this->scalar($fields, 'username');
            $home = $this->scalar($fields, 'home_directory');
            $type = $this->scalar($fields, 'type');
            if (! preg_match('/^[0-9]{1,32}$/D', $id) || ! preg_match('/^[a-z_][a-z0-9_.-]{0,63}$/Di', $user)
                || ! str_starts_with($home, '/') || $home === '/' || preg_match('~(?:^|/)\.\.?(/|$)|[\x00-\x1f\x7f]~', $home)
                || ! in_array($type, ['Top-level server', 'Sub-server', 'Alias', 'Alias with own email'], true)) {
                throw new RuntimeException('Source identities, home paths or server types are not supported.');
            }
            if (isset($sourceIds[$id])) {
                throw new RuntimeException('The source returned duplicate numeric domain identities.');
            }
            $sourceIds[$id] = true;
            $features = preg_split('/\s+/', trim($this->scalar($fields, 'features', false)), flags: PREG_SPLIT_NO_EMPTY);
            $plugins = preg_split('/\s+/', trim($this->scalar($fields, 'plugins', false)), flags: PREG_SPLIT_NO_EMPTY);
            foreach ([...$features, ...$plugins] as $feature) {
                if (! preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $feature)) {
                    throw new RuntimeException('The source feature inventory contains invalid identifiers.');
                }
            }
            // The API can include plaintext or hashed passwords: never return them in inventory.
            $domains[] = ['domain' => $domain, 'source_id' => $id, 'username' => $user, 'home' => $home, 'type' => $type,
                'features' => $features, 'plugins' => $plugins,
                'parent' => isset($fields['parent_domain']) ? $this->domain($this->scalar($fields, 'parent_domain')) : null,
                'alias_target' => isset($fields['real_domain']) ? $this->domain($this->scalar($fields, 'real_domain')) : null,
                'disabled' => isset($fields['disabled']),
            ];
        }
        if (! $allDomains && array_diff($approved, array_keys($seen)) !== []) {
            throw new RuntimeException('An approved domain is absent from the source; partial inventory was not accepted.');
        }
        $selected = array_column($domains, 'domain');
        foreach ($domains as $domain) {
            foreach (['parent', 'alias_target'] as $field) {
                if ($domain[$field] !== null && ! in_array($domain[$field], $selected, true)) {
                    throw new RuntimeException('Approve parent and alias-target domains together before planning a migration.');
                }
            }
        }
        $remaining = array_fill_keys($selected, 0);
        $dependents = [];
        foreach ($domains as $domain) {
            $dependencies = array_unique(array_filter([$domain['parent'], $domain['alias_target']]));
            $remaining[$domain['domain']] = count($dependencies);
            foreach ($dependencies as $dependency) {
                $dependents[$dependency][] = $domain['domain'];
            }
            if (($domain['type'] === 'Sub-server' && $domain['parent'] === null)
                || (str_starts_with($domain['type'], 'Alias') && $domain['alias_target'] === null)) {
                throw new RuntimeException('Sub-servers and aliases must identify their source parent or target.');
            }
        }
        $queue = array_keys(array_filter($remaining, fn (int $count): bool => $count === 0));
        for ($offset = 0; $offset < count($queue); $offset++) {
            foreach ($dependents[$queue[$offset]] ?? [] as $dependent) {
                if (--$remaining[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }
        if (count($queue) !== count($domains)) {
            throw new RuntimeException('The source contains cyclic parent or alias dependencies.');
        }
        usort($domains, fn (array $left, array $right): int => strcmp($left['domain'], $right['domain']));

        return ['provider' => 'virtualmin8', 'version' => $version[1], 'edition' => strcasecmp($edition[1], 'GPL') === 0 ? 'gpl' : 'pro', 'domains' => $domains, 'excluded_domains' => $excluded];
    }

    private function domain(string $domain): string
    {
        $domain = strtolower(rtrim($domain, '.'));
        if (strlen($domain) > 253 || ! str_contains($domain, '.') || ! preg_match('/^[a-z0-9.-]+$/D', $domain)) {
            throw new InvalidArgumentException('Source domains must be fully qualified ASCII hostnames.');
        }
        foreach (explode('.', $domain) as $label) {
            if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label)) {
                throw new InvalidArgumentException('A source domain contains an invalid hostname label.');
            }
        }

        return $domain;
    }

    /** @param array<string, mixed> $fields */
    private function scalar(array $fields, string $name, bool $required = true): string
    {
        $values = $fields[$name] ?? [];
        if (! is_array($values) || (! $required && $values === []) || count($values) !== 1 || ! is_string($values[0] ?? null)) {
            if (! $required && $values === []) {
                return '';
            }
            throw new RuntimeException('A source inventory field is missing, duplicated or malformed.');
        }

        return $values[0];
    }
}
