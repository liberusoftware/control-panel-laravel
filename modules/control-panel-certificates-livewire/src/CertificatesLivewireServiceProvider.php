<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\CertificatesLivewire\Components\CertificateInventory;
use Liberu\ControlPanel\CertificatesLivewire\Components\CertificateLifecycleInventory;
use Liberu\ControlPanel\CertificatesLivewire\Components\CertificateOperationInventory;
use Livewire\Livewire;

final class CertificatesLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-certificates-livewire');
        Livewire::component('module-control-panel-certificates::certificate-inventory', CertificateInventory::class);
        Livewire::component('module-control-panel-certificates::certificate-operation-inventory', CertificateOperationInventory::class);
        Livewire::component('module-control-panel-certificates::certificate-lifecycle-inventory', CertificateLifecycleInventory::class);
    }
}
