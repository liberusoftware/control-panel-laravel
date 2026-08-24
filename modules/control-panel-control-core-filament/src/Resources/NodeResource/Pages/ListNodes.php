<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeResource;

final class ListNodes extends ListRecords
{
    protected static string $resource = NodeResource::class;
}
