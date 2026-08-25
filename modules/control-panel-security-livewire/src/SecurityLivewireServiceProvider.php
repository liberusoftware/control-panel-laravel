<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\SecurityLivewire\Components\FindingInventory;
use Liberu\ControlPanel\SecurityLivewire\Components\HardeningInventory;
use Livewire\Livewire;

final class SecurityLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-security-livewire');
        Livewire::component('module-control-panel-security::finding-inventory', FindingInventory::class);
        Livewire::component('module-control-panel-security::hardening-inventory', HardeningInventory::class);
    }
}
