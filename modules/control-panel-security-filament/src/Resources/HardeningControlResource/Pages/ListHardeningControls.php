<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource;

final class ListHardeningControls extends ListRecords
{
    protected static string $resource = HardeningControlResource::class;
}
