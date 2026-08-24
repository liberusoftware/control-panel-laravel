<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\FilesLivewire\Components\FileInventory;
use Livewire\Livewire;

final class FilesLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-files-livewire');
        Livewire::component('module-control-panel-files::file-inventory', FileInventory::class);
    }
}
