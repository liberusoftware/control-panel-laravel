<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Monitoring\Actions\ResolveMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;
use Livewire\Component;

final class MonitoringFeatureInventory extends Component
{
    public int $perPage = 25;

    public function resolveEvent(string $eventId, ResolveMonitoringEvent $resolve): void
    {
        $event = MonitoringEvent::query()->whereKey($eventId)->where('team_id', auth()->user()?->current_team_id)->firstOrFail();
        $resolve->execute($event);
    }

    public function render(): View
    {
        $events = MonitoringEvent::query()->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-monitoring-livewire::components.monitoring-feature-inventory', ['events' => $events]);
    }
}
