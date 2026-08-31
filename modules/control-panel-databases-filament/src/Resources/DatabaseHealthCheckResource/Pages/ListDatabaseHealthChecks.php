<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseHealthCheckResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseHealthCheckResource;

final class ListDatabaseHealthChecks extends ListRecords
{
    protected static string $resource = DatabaseHealthCheckResource::class;
}
