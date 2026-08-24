<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\BackupsLivewire\Components\SnapshotInventory;
use Livewire\Livewire;

final class BackupsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-backups-livewire');
        Livewire::component('module-control-panel-backups::snapshot-inventory', SnapshotInventory::class);
    }
}
