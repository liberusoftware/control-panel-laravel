<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\MailLivewire\Components\MailInventory;
use Livewire\Livewire;

final class MailLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'control-panel-mail-livewire');
        Livewire::component('module-control-panel-mail::mail-inventory', MailInventory::class);
    }
}
