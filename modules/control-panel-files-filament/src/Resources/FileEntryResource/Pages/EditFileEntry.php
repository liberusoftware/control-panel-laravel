<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource;

final class EditFileEntry extends EditRecord
{
    protected static string $resource = FileEntryResource::class;
}
