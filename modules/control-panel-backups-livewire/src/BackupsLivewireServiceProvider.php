<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\BackupsLivewire\Components\BackupExecutionInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\DestinationInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\PolicyInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\ScheduleInventory;
use Liberu\ControlPanel\BackupsLivewire\Components\SnapshotInventory;
use Livewire\Livewire;

final class BackupsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-backups-livewire');
        Livewire::component('module-control-panel-backups::snapshot-inventory', SnapshotInventory::class);
        Livewire::component('module-control-panel-backups::backup-execution-inventory', BackupExecutionInventory::class);
        Livewire::component('module-control-panel-backups::policy-inventory', PolicyInventory::class);
        Livewire::component('module-control-panel-backups::destination-inventory', DestinationInventory::class);
        Livewire::component('module-control-panel-backups::schedule-inventory', ScheduleInventory::class);
    }
}
