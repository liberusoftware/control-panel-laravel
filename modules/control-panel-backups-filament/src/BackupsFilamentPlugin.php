<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupEncryptionResource;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupExecutionResource;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupRestoreResource;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource;
use Liberu\ControlPanel\BackupsFilament\Resources\OffsiteTransferResource;

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
        $panel->resources([
            BackupSnapshotResource::class,
            BackupPolicyResource::class,
            BackupDestinationResource::class,
            BackupScheduleResource::class,
            BackupExecutionResource::class,
            BackupEncryptionResource::class,
            BackupRestoreResource::class,
            OffsiteTransferResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
