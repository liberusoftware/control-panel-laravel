<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource;

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
        $panel->resources([OsAdapterResource::class]);
    }

    public function boot(Panel $panel): void {}
}
