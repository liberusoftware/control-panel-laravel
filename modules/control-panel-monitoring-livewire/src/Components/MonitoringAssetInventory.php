<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Monitoring\Models\AlertRule;
use Liberu\ControlPanel\Monitoring\Models\CapacitySnapshot;
use Liberu\ControlPanel\Monitoring\Models\Incident;
use Liberu\ControlPanel\Monitoring\Models\LogEntry;
use Liberu\ControlPanel\Monitoring\Models\MetricSample;
use Liberu\ControlPanel\Monitoring\Models\StatusSnapshot;
use Liberu\ControlPanel\Monitoring\Models\UptimeCheck;
use Livewire\Component;

final class MonitoringAssetInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $limit = min(max($this->perPage, 1), 100);
        $models = [
            'metrics' => MetricSample::class,
            'logs' => LogEntry::class,
            'uptime' => UptimeCheck::class,
            'capacity' => CapacitySnapshot::class,
            'alert rules' => AlertRule::class,
            'incidents' => Incident::class,
            'status' => StatusSnapshot::class,
        ];
        $assets = collect($models)->mapWithKeys(fn (string $model, string $key): array => [$key => $model::query()->where('team_id', $teamId)->latest()->limit($limit)->get()]);

        return view('control-panel-monitoring-livewire::components.monitoring-asset-inventory', ['assets' => $assets]);
    }
}
