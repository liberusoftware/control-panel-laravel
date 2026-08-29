<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource;

final class ListHomeDirectories extends ListRecords
{
    protected static string $resource = HomeDirectoryResource::class;
}
