<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\Record;
use Liberu\ControlPanel\Dns\Models\Zone;

final class UpdateRecord
{
    /** @param array<string, mixed> $attributes */
    public function execute(Record $record, array $attributes): Record
    {
        $zoneId = $attributes['zone_id'] ?? $record->zone_id;
        $name = trim((string) ($attributes['name'] ?? $record->name));
        $type = strtoupper(trim((string) ($attributes['type'] ?? $record->type)));
        $content = trim((string) ($attributes['content'] ?? $record->content));
        $ttl = (int) ($attributes['ttl'] ?? $record->ttl);
        $priority = $attributes['priority'] ?? $record->priority;
        $zone = Zone::query()->whereKey($zoneId)->where('team_id', $record->zone->team_id)->first();

        if ($zone === null) {
            throw ValidationException::withMessages(['zone_id' => 'The zone must belong to the current team.']);
        }
        if (! in_array($type, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA'], true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported DNS record type.']);
        }
        if ($name === '' || mb_strlen($name) > 253 || $content === '') {
            throw ValidationException::withMessages(['record' => 'A record name and content are required.']);
        }
        if ($ttl < 60 || $ttl > 86400 || ($priority !== null && ((int) $priority < 0 || (int) $priority > 65535))) {
            throw ValidationException::withMessages(['ttl' => 'The TTL or priority is outside the supported range.']);
        }

        $record->forceFill([
            'zone_id' => $zoneId,
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
            'priority' => $priority,
            'metadata' => $attributes['metadata'] ?? $record->metadata,
        ])->save();

        return $record->refresh();
    }
}
