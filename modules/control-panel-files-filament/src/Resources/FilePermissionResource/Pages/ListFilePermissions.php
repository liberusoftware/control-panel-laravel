<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource;

final class ListFilePermissions extends ListRecords
{
    protected static string $resource = FilePermissionResource::class;
}
