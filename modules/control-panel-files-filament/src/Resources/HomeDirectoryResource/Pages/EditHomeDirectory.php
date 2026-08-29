<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource;

final class EditHomeDirectory extends EditRecord
{
    protected static string $resource = HomeDirectoryResource::class;
}
