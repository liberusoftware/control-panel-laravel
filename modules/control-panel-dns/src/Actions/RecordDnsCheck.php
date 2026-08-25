<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Support\Str;
use Liberu\ControlPanel\Dns\Models\DnsCheck;

final class RecordDnsCheck
{
    public function execute(array $a): DnsCheck
    {
        return DnsCheck::query()->create(['id' => (string) Str::uuid(), 'team_id' => $a['team_id'] ?? null, 'zone_id' => $a['zone_id'] ?? null, 'kind' => $a['kind'] ?? 'propagation', 'status' => $a['status'] ?? 'pending', 'result' => $a['result'] ?? [], 'checked_at' => now()]);
    }
}
