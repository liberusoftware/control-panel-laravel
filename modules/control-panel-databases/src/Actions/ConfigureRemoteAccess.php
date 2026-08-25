<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseRemoteAccess;

final class ConfigureRemoteAccess
{
    /** @param array<string, mixed> $attributes */
    public function execute(Database $database, array $attributes): DatabaseRemoteAccess
    {
        $cidr = trim((string) ($attributes['source_cidr'] ?? ''));
        [$address, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);
        $maxPrefix = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 128 : 32;
        if ($cidr === '' || filter_var($address, FILTER_VALIDATE_IP) === false || ($prefix !== null && (! ctype_digit($prefix) || (int) $prefix > $maxPrefix))) {
            throw ValidationException::withMessages(['source_cidr' => 'A valid source IP or CIDR is required.']);
        }
        $port = (int) ($attributes['port'] ?? 0);
        if ($port < 1 || $port > 65535) {
            throw ValidationException::withMessages(['port' => 'The port must be between 1 and 65535.']);
        }

        return DatabaseRemoteAccess::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $database->team_id, 'database_id' => $database->getKey(),
            'source_cidr' => $cidr, 'port' => $port, 'tls_required' => (bool) ($attributes['tls_required'] ?? true),
            'active' => true, 'expires_at' => $attributes['expires_at'] ?? null, 'metadata' => $attributes['metadata'] ?? [],
        ]);
    }
}
