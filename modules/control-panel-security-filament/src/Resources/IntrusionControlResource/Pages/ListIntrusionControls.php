<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\IntrusionControlResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\IntrusionControlResource;

final class ListIntrusionControls extends ListRecords
{
    protected static string $resource = IntrusionControlResource::class;
}
