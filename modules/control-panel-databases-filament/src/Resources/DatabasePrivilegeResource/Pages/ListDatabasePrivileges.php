<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabasePrivilegeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabasePrivilegeResource;

final class ListDatabasePrivileges extends ListRecords
{
    protected static string $resource = DatabasePrivilegeResource::class;
}
