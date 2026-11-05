<?php

use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\HostingRuntimeServiceProvider;
use App\Providers\HostingTelemetryServiceProvider;
use Liberu\Foundation\ModuleManager\ModuleManagerServiceProvider;

return [
    ModuleManagerServiceProvider::class,
    HostingRuntimeServiceProvider::class,
    HostingTelemetryServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
];
