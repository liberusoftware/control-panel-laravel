<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\Dns\Models\Zone;

final class CreateRecord
{
    public function execute(array $a): Record
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team context is required.']);
        }

        $type = strtoupper(trim((string) ($a['type'] ?? '')));
        if (! in_array($type, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA'], true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported DNS record type.']);
        } if (trim((string) ($a['content'] ?? '')) === '') {
            throw ValidationException::withMessages(['content' => 'Record content is required.']);
        }

        $zone = Zone::query()->whereKey($a['zone_id'] ?? null)->where('team_id', $teamId)->firstOrFail();

        return Record::query()->create(['id' => (string) Str::uuid(), 'zone_id' => $zone->getKey(), 'name' => trim((string) ($a['name'] ?? '@')), 'type' => $type, 'content' => trim((string) $a['content']), 'ttl' => max((int) ($a['ttl'] ?? 3600), 60), 'priority' => $a['priority'] ?? null, 'metadata' => $a['metadata'] ?? []]);
    }
}
