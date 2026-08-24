<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\KubernetesLivewire\Components\ClusterInventory;
use Livewire\Livewire;

final class KubernetesLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-kubernetes-livewire');
        Livewire::component('module-control-panel-kubernetes::cluster-inventory', ClusterInventory::class);
    }
}
