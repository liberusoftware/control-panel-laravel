<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseRemoteAccessResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseRemoteAccessResource;

final class ListDatabaseRemoteAccess extends ListRecords
{
    protected static string $resource = DatabaseRemoteAccessResource::class;
}
