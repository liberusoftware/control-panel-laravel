<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ContainersLivewire\Components\WorkloadInventory;
use Liberu\ControlPanel\ContainersLivewire\Components\ContainerAssetInventory;
use Livewire\Livewire;

final class ContainersLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-containers-livewire');
        Livewire::component('module-control-panel-containers::workload-inventory', WorkloadInventory::class);
        Livewire::component('module-control-panel-containers::container-asset-inventory', ContainerAssetInventory::class);
    }
}
