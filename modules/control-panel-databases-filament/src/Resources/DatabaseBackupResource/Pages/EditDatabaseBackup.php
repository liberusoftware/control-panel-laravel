<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource;

final class EditDatabaseBackup extends EditRecord
{
    protected static string $resource = DatabaseBackupResource::class;
}
