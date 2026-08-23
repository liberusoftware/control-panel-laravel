<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ControlCoreLivewire\Components\NodeInventory;
use Livewire\Livewire;

final class ControlCoreLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-control-core-livewire');
        Livewire::component('module-control-panel-control-core::node-inventory', NodeInventory::class);
    }
}
