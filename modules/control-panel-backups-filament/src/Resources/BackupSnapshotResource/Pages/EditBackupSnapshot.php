<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource;

final class EditBackupSnapshot extends EditRecord
{
    protected static string $resource = BackupSnapshotResource::class;
}
