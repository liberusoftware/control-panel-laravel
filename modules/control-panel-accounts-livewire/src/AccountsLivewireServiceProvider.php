<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\AccountsLivewire\Components\AccountInventory;
use Liberu\ControlPanel\AccountsLivewire\Components\AccountFeatureInventory;
use Livewire\Livewire;

final class AccountsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-accounts-livewire');
        Livewire::component('module-control-panel-accounts::account-inventory', AccountInventory::class);
        Livewire::component('module-control-panel-accounts::feature-inventory', AccountFeatureInventory::class);
    }
}
