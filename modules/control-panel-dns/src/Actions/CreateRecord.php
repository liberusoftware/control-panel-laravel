<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\Record;

final class CreateRecord
{
    public function execute(array $a): Record
    {
        $type = strtoupper(trim((string) ($a['type'] ?? '')));
        if (! in_array($type, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA'], true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported DNS record type.']);
        } if (trim((string) ($a['content'] ?? '')) === '') {
            throw ValidationException::withMessages(['content' => 'Record content is required.']);
        }

        return Record::query()->create(['id' => (string) Str::uuid(), 'zone_id' => $a['zone_id'], 'name' => trim((string) ($a['name'] ?? '@')), 'type' => $type, 'content' => trim((string) $a['content']), 'ttl' => max((int) ($a['ttl'] ?? 3600), 60), 'priority' => $a['priority'] ?? null, 'metadata' => $a['metadata'] ?? []]);
    }
}
