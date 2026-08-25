<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource;

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
        $panel->resources([DatabaseResource::class, DatabaseEngineResource::class, DatabaseBackupResource::class]);
    }

    public function boot(Panel $panel): void {}
}
