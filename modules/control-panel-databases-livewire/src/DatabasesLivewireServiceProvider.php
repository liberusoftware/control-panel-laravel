<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\DatabasesLivewire\Components\BackupInventory;
use Liberu\ControlPanel\DatabasesLivewire\Components\DatabaseFeatureInventory;
use Liberu\ControlPanel\DatabasesLivewire\Components\DatabaseInventory;
use Livewire\Livewire;

final class DatabasesLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-databases-livewire');
        Livewire::component('module-control-panel-databases::database-inventory', DatabaseInventory::class);
        Livewire::component('module-control-panel-databases::backup-inventory', BackupInventory::class);
        Livewire::component('module-control-panel-databases::database-feature-inventory', DatabaseFeatureInventory::class);
    }
}
