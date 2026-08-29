<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsServiceResource;

final class ListOsServices extends ListRecords
{
    protected static string $resource = OsServiceResource::class;
}
