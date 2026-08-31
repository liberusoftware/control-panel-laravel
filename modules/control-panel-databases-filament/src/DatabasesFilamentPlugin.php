<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseHealthCheckResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabasePrivilegeResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseRemoteAccessResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUpgradeResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUserResource;

final class DatabasesFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-databases-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            DatabaseResource::class,
            DatabaseEngineResource::class,
            DatabaseBackupResource::class,
            DatabaseUserResource::class,
            DatabasePrivilegeResource::class,
            DatabaseUpgradeResource::class,
            DatabaseHealthCheckResource::class,
            DatabaseRemoteAccessResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
