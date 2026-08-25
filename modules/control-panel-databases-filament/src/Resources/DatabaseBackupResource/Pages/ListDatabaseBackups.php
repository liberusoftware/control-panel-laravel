<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource;

final class ListDatabaseBackups extends ListRecords
{
    protected static string $resource = DatabaseBackupResource::class;
}
