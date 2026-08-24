<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource;

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
        $panel->resources([MonitorResource::class]);
    }

    public function boot(Panel $panel): void {}
}
