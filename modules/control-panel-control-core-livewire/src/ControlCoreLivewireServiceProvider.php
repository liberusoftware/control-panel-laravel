<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ControlCoreLivewire\Components\NodeInventory;
use Liberu\ControlPanel\ControlCoreLivewire\Components\CredentialInventory;
use Liberu\ControlPanel\ControlCoreLivewire\Components\OperationsInventory;
use Livewire\Livewire;

final class ControlCoreLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-control-core-livewire');
        Livewire::component('module-control-panel-control-core::node-inventory', NodeInventory::class);
        Livewire::component('module-control-panel-control-core::credential-inventory', CredentialInventory::class);
        Livewire::component('module-control-panel-control-core::operations-inventory', OperationsInventory::class);
    }
}
