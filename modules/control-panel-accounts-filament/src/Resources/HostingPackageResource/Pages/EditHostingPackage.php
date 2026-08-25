<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\AccountsFilament\Resources\HostingPackageResource;

final class EditHostingPackage extends EditRecord
{
    protected static string $resource = HostingPackageResource::class;
}
