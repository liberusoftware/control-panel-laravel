<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource;

final class EditFilePermission extends EditRecord
{
    protected static string $resource = FilePermissionResource::class;
}
