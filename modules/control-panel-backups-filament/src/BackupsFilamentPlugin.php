<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource;

final class BackupsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'control-panel-backups-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([BackupSnapshotResource::class]);
    }

    public function boot(Panel $panel): void {}
}
