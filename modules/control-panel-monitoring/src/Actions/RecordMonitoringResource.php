<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Monitoring\Models\AlertRule;
use Liberu\ControlPanel\Monitoring\Models\CapacitySnapshot;
use Liberu\ControlPanel\Monitoring\Models\Incident;
use Liberu\ControlPanel\Monitoring\Models\LogEntry;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Models\MetricSample;
use Liberu\ControlPanel\Monitoring\Models\StatusSnapshot;
use Liberu\ControlPanel\Monitoring\Models\UptimeCheck;

final class RecordMonitoringResource
{
    public function execute(array $a): Model
    {
        $kind = (string) ($a['kind'] ?? '');
        $map = ['metric' => MetricSample::class, 'log' => LogEntry::class, 'uptime' => UptimeCheck::class, 'capacity' => CapacitySnapshot::class, 'alert' => AlertRule::class, 'incident' => Incident::class, 'maintenance' => MaintenanceWindow::class, 'status' => StatusSnapshot::class];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported monitoring resource.']);
        } $attributes = $a;
        unset($attributes['kind']);
        $attributes['id'] = $attributes['id'] ?? (string) Str::uuid();
        $attributes['team_id'] = $a['team_id'] ?? null;
        if ($kind === 'maintenance') {
            $attributes['status'] = $attributes['status'] ?? 'scheduled';
        } if ($kind === 'incident') {
            $attributes['status'] = $attributes['status'] ?? 'open';
        }

        return $map[$kind]::query()->create($attributes);
    }
}
