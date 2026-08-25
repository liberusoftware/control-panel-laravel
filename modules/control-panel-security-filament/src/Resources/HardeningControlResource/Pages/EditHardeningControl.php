<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource;

final class EditHardeningControl extends EditRecord
{
    protected static string $resource = HardeningControlResource::class;
}
