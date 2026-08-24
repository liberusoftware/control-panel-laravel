<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource;

final class ListBackupSnapshots extends ListRecords
{
    protected static string $resource = BackupSnapshotResource::class;
}
