<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsPackageResource;

final class EditOsPackage extends EditRecord
{
    protected static string $resource = OsPackageResource::class;
}
