<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource;

final class ListOsPackages extends ListRecords
{
    protected static string $resource = OsPackageResource::class;
}
