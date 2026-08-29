<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Contracts;

interface DnsResolver
{
    /** @return array<int, array<string, mixed>> */
    public function records(string $hostname, string $recordType): array;

    /** @return array<int, string> */
    public function nameservers(string $hostname): array;
}
