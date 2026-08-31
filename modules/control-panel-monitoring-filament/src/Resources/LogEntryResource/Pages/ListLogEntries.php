<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\LogEntryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\LogEntryResource;

final class ListLogEntries extends ListRecords
{
    protected static string $resource = LogEntryResource::class;
}
