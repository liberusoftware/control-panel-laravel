<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUserResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUserResource;

final class ListDatabaseUsers extends ListRecords
{
    protected static string $resource = DatabaseUserResource::class;
}
