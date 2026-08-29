<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\ApiAutomationLivewire\Components\AutomationFeatureInventory;
use Liberu\ControlPanel\ApiAutomationLivewire\Components\AutomationInventory;
use Liberu\ControlPanel\ApiAutomationLivewire\Components\CredentialInventory;
use Liberu\ControlPanel\ApiAutomationLivewire\Components\WebhookInventory;
use Livewire\Livewire;

final class ApiAutomationLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-api-and-automation-livewire');
        Livewire::component('module-control-panel-api-and-automation::automation-inventory', AutomationInventory::class);
        Livewire::component('module-control-panel-api-and-automation::webhook-inventory', WebhookInventory::class);
        Livewire::component('module-control-panel-api-and-automation::feature-inventory', AutomationFeatureInventory::class);
        Livewire::component('module-control-panel-api-and-automation::credential-inventory', CredentialInventory::class);
    }
}
