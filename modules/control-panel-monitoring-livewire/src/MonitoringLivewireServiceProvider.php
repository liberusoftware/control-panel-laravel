<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\MonitoringLivewire\Components\MonitorInventory;
use Liberu\ControlPanel\MonitoringLivewire\Components\MonitoringFeatureInventory;
use Livewire\Livewire;

final class MonitoringLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-monitoring-livewire');
        Livewire::component('module-control-panel-monitoring::monitor-inventory', MonitorInventory::class);
        Livewire::component('module-control-panel-monitoring::monitoring-feature-inventory', MonitoringFeatureInventory::class);
    }
}
