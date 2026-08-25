<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource;

final class ListOsAdapters extends ListRecords
{
    protected static string $resource = OsAdapterResource::class;
}
