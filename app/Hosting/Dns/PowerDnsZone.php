<?php

namespace App\Hosting\Dns;

use InvalidArgumentException;

final class PowerDnsZone
{
    public function domain(string $value): string
    {
        $value = strtolower(rtrim($value, '.'));
        $this->ensure(strlen($value) <= 253 && str_contains($value, '.') && preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/D', $value), 'A fully qualified ASCII zone name is required.');
        foreach (explode('.', $value) as $label) {
            $this->ensure((bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label), 'Invalid DNS name label.');
        }

        return $value.'.';
    }

    /** @param iterable<array<string, mixed>> $records
     * @return list<array{name: string, type: string, ttl: int, records: list<array{content: string, disabled: false}>}>
     */
    public function rrsets(string $domain, iterable $records): array
    {
        $domain = $this->domain($domain);
        $sets = [];
        $count = 0;
        foreach ($records as $record) {
            $this->ensure(++$count <= 10000, 'A zone may contain at most 10000 inventory records.');
            $name = strtolower((string) ($record['name'] ?? '@'));
            $name = $name === '@' ? $domain : (str_ends_with($name, '.') ? $name : $name.'.'.$domain);
            $this->ensure(strlen($name) <= 254 && ($name === $domain || str_ends_with($name, '.'.$domain)), 'Record owners must remain inside the allowlisted zone.');
            foreach (explode('.', rtrim($name, '.')) as $index => $label) {
                $this->ensure(($index === 0 && $label === '*') || (bool) preg_match('/^[a-z0-9_](?:[a-z0-9_-]{0,61}[a-z0-9_])?$/D', $label), 'Invalid record owner label.');
            }
            $type = strtoupper((string) ($record['type'] ?? ''));
            $ttl = $record['ttl'] ?? null;
            $this->ensure(is_int($ttl) && $ttl >= 60 && $ttl <= 2147483647, 'Record TTL must be between 60 and 2147483647.');
            $content = $this->content($type, (string) ($record['content'] ?? ''), $record['priority'] ?? null);
            $key = $name.'|'.$type;
            $this->ensure(! isset($sets[$key]) || $sets[$key]['ttl'] === $ttl, 'All records in an RRset must have the same TTL.');
            $sets[$key] ??= ['name' => $name, 'type' => $type, 'ttl' => $ttl, 'records' => []];
            $sets[$key]['records'][$content] = ['content' => $content, 'disabled' => false];
        }
        $this->ensure(count($sets[$domain.'|NS']['records'] ?? []) >= 2, 'At least two distinct apex nameservers are required.');
        foreach ($sets as $set) {
            if ($set['type'] === 'CNAME') {
                $this->ensure($set['name'] !== $domain && count($set['records']) === 1, 'A CNAME must have one target and cannot be at the zone apex.');
                foreach ($sets as $other) {
                    $this->ensure($other['name'] !== $set['name'] || $other['type'] === 'CNAME', 'A CNAME cannot coexist with other record types.');
                }
            }
        }
        ksort($sets);
        foreach ($sets as &$set) {
            ksort($set['records']);
            $set['records'] = array_values($set['records']);
        }

        return array_values($sets);
    }

    private function content(string $type, string $content, mixed $priority): string
    {
        $this->ensure($content !== '' && strlen($content) <= 4096 && ! preg_match('/[^\x20-\x7e]/', $content), 'DNS content must be printable ASCII without control characters.');

        return match ($type) {
            'A', 'AAAA' => $this->address($type, $content),
            'NS', 'CNAME' => $this->domain($content),
            'MX' => $this->priority($priority).' '.$this->domain($content),
            'TXT' => implode(' ', array_map(fn (string $chunk): string => '"'.addcslashes($chunk, '\\"').'"', str_split($content, 200))),
            'SRV' => $this->srv($content),
            'CAA' => $this->caa($content),
            default => throw new InvalidArgumentException('Unsupported DNS record type.'),
        };
    }

    private function address(string $type, string $content): string
    {
        $this->ensure(filter_var($content, FILTER_VALIDATE_IP, $type === 'A' ? FILTER_FLAG_IPV4 : FILTER_FLAG_IPV6) !== false, 'Invalid DNS address.');

        return inet_ntop(inet_pton($content));
    }

    private function priority(mixed $value): int
    {
        $this->ensure(is_int($value) && $value >= 0 && $value <= 65535, 'DNS priority must be an integer between 0 and 65535.');

        return $value;
    }

    private function srv(string $content): string
    {
        $parts = preg_split('/\s+/', trim($content));
        $this->ensure(count($parts) === 4, 'SRV requires priority, weight, port and target.');
        foreach (array_slice($parts, 0, 3) as $part) {
            $this->ensure(ctype_digit($part), 'Invalid SRV numeric field.');
            $this->priority((int) $part);
        }

        return implode(' ', [(int) $parts[0], (int) $parts[1], (int) $parts[2], $parts[3] === '.' ? '.' : $this->domain($parts[3])]);
    }

    private function caa(string $content): string
    {
        $this->ensure((bool) preg_match('/^(0|[1-9][0-9]{0,2}) ([a-zA-Z0-9]+) "([^"\\\\]*)"$/D', $content, $matches) && (int) $matches[1] <= 255, 'Invalid CAA content.');

        return $matches[1].' '.strtolower($matches[2]).' "'.$matches[3].'"';
    }

    private function ensure(bool|int $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidArgumentException($message);
        }
    }
}
