<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\MonitoringFilament\Resources\AlertRuleResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\CapacitySnapshotResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\IncidentResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\LogEntryResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\MetricSampleResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\StatusSnapshotResource;
use Liberu\ControlPanel\MonitoringFilament\Resources\UptimeCheckResource;

final class MonitoringFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-monitoring-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MonitorResource::class,
            MonitoringEventResource::class,
            MaintenanceWindowResource::class,
            MetricSampleResource::class,
            LogEntryResource::class,
            UptimeCheckResource::class,
            CapacitySnapshotResource::class,
            AlertRuleResource::class,
            IncidentResource::class,
            StatusSnapshotResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
