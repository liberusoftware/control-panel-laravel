<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUpgradeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUpgradeResource;

final class ListDatabaseUpgrades extends ListRecords
{
    protected static string $resource = DatabaseUpgradeResource::class;
}
