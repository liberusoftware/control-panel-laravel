<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Support;

use Liberu\ControlPanel\Dns\Contracts\DnsResolver;

final class NativeDnsResolver implements DnsResolver
{
    public function records(string $hostname, string $recordType): array
    {
        $constant = 'DNS_'.strtoupper($recordType);
        $type = defined($constant) ? constant($constant) : DNS_ANY;
        $records = dns_get_record($hostname, $type);

        return is_array($records) ? $records : [];
    }

    public function nameservers(string $hostname): array
    {
        return array_values(array_unique(array_map(
            static fn (array $record): string => rtrim((string) ($record['target'] ?? ''), '.'),
            $this->records($hostname, 'NS'),
        )));
    }
}
