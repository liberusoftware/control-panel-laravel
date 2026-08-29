<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Monitoring\Actions\CancelMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\DeleteMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Actions\ResolveMonitoringEvent;
use Liberu\ControlPanel\Monitoring\Actions\UpdateMaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Models\MaintenanceWindow;
use Liberu\ControlPanel\Monitoring\Models\MonitoringEvent;
use Livewire\Component;

final class MonitoringFeatureInventory extends Component
{
    public int $perPage = 25;

    public function cancelMaintenance(string $windowId, CancelMaintenanceWindow $cancel): void
    {
        $window = MaintenanceWindow::query()->whereKey($windowId)->where('team_id', $this->teamId())->firstOrFail();
        $cancel->execute($window);
    }

    public function deleteMaintenance(string $windowId, DeleteMaintenanceWindow $delete): void
    {
        $window = MaintenanceWindow::query()->whereKey($windowId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($window);
    }

    /** @param array<string, mixed> $attributes */
    public function updateMaintenance(string $windowId, array $attributes, UpdateMaintenanceWindow $update): void
    {
        $window = MaintenanceWindow::query()->whereKey($windowId)->where('team_id', $this->teamId())->firstOrFail();
        $update->execute($window, $attributes);
    }

    public function resolveEvent(string $eventId, ResolveMonitoringEvent $resolve): void
    {
        $event = MonitoringEvent::query()->whereKey($eventId)->where('team_id', $this->teamId())->firstOrFail();
        $resolve->execute($event);
    }

    public function render(): View
    {
        $events = MonitoringEvent::query()->where('team_id', $this->teamId())->latest()->paginate(min(max($this->perPage, 1), 100));

        $maintenance = MaintenanceWindow::query()->where('team_id', $this->teamId())->latest('starts_at')->limit(10)->get();

        return view('control-panel-monitoring-livewire::components.monitoring-feature-inventory', ['events' => $events, 'maintenance' => $maintenance]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
