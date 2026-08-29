<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource;

final class ListBackupDestinations extends ListRecords
{
    protected static string $resource = BackupDestinationResource::class;
}
