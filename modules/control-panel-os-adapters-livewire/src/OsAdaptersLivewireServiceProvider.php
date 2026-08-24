<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\OsAdaptersLivewire\Components\OsAdapterInventory;
use Liberu\ControlPanel\OsAdaptersLivewire\Components\PackageInventory;
use Livewire\Livewire;

final class OsAdaptersLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-os-adapters-livewire');
        Livewire::component('module-control-panel-os-adapters::os-adapter-inventory', OsAdapterInventory::class);
        Livewire::component('module-control-panel-os-adapters::package-inventory', PackageInventory::class);
    }
}
