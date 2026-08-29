<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Models\Monitor;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;

final class RecordMonitoringEvent
{
    public function execute(array $a): MonitoringEvent
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        $kind = (string) ($a['kind'] ?? '');
        if (! in_array($kind, ['metric', 'log', 'uptime', 'capacity', 'alert', 'incident', 'maintenance', 'status'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported monitoring event.']);
        }

        $monitorId = $a['monitor_id'] ?? null;
        if ($monitorId !== null && ! Monitor::query()->whereKey($monitorId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return MonitoringEvent::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'monitor_id' => $monitorId, 'kind' => $kind, 'status' => $a['status'] ?? 'open', 'payload' => $a['payload'] ?? [], 'starts_at' => $a['starts_at'] ?? now(), 'ends_at' => $a['ends_at'] ?? null]);
    }
}
