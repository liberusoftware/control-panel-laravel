<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource;

final class ListFileEntries extends ListRecords
{
    protected static string $resource = FileEntryResource::class;
}
