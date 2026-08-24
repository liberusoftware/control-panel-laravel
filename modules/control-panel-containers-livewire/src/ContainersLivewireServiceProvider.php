<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ContainersLivewire\Components\WorkloadInventory;
use Livewire\Livewire;

final class ContainersLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-containers-livewire');
        Livewire::component('module-control-panel-containers::workload-inventory', WorkloadInventory::class);
    }
}
