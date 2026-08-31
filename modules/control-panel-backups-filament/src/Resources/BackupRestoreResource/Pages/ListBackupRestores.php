<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupRestoreResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupRestoreResource;

final class ListBackupRestores extends ListRecords
{
    protected static string $resource = BackupRestoreResource::class;
}
