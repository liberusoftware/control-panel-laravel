<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource;

final class ListBackupSchedules extends ListRecords
{
    protected static string $resource = BackupScheduleResource::class;
}
