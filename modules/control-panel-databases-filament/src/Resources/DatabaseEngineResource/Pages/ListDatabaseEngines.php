<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource;

final class ListDatabaseEngines extends ListRecords
{
    protected static string $resource = DatabaseEngineResource::class;
}
