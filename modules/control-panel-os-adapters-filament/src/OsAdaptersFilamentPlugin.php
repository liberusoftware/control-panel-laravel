<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\SupportMatrixResource;

final class OsAdaptersFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-os-adapters-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([OsAdapterResource::class, OsPackageResource::class, OsServiceResource::class, FirewallRuleResource::class, SupportMatrixResource::class]);
    }

    public function boot(Panel $panel): void {}
}
