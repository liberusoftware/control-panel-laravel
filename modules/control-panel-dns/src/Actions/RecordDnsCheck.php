<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\DnsCheck;
use Liberu\ControlPanel\Dns\Models\Zone;

final class RecordDnsCheck
{
    public function execute(array $a): DnsCheck
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        $zoneId = $a['zone_id'] ?? null;
        $kind = (string) ($a['kind'] ?? 'propagation');
        $status = (string) ($a['status'] ?? 'pending');

        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A tenant is required.']);
        }
        if (! in_array($kind, ['validation', 'propagation', 'dnssec'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported DNS check kind.']);
        }
        if (! in_array($status, ['pending', 'passed', 'failed'], true)) {
            throw ValidationException::withMessages(['status' => 'Unsupported DNS check status.']);
        }
        if ($zoneId !== null && ! Zone::query()->whereKey($zoneId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return DnsCheck::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'zone_id' => $zoneId, 'kind' => $kind, 'status' => $status, 'result' => $a['result'] ?? [], 'checked_at' => now()]);
    }
}
