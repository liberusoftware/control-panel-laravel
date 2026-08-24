<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\DnsLivewire\Components\ZoneInventory;
use Liberu\ControlPanel\DnsLivewire\Components\DnsFeatureInventory;
use Livewire\Livewire;

final class DnsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-dns-livewire');
        Livewire::component('module-control-panel-dns::zone-inventory', ZoneInventory::class);
        Livewire::component('module-control-panel-dns::dns-feature-inventory', DnsFeatureInventory::class);
    }
}
