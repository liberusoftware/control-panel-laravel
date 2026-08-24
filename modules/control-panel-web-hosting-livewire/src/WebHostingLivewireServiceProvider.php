<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\WebHostingLivewire\Components\DomainInventory;
use Liberu\ControlPanel\WebHostingLivewire\Components\HostingResourceInventory;
use Livewire\Livewire;

final class WebHostingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-web-hosting-livewire');
        Livewire::component('module-control-panel-web-hosting::domain-inventory', DomainInventory::class);
        Livewire::component('module-control-panel-web-hosting::hosting-resource-inventory', HostingResourceInventory::class);
    }
}
