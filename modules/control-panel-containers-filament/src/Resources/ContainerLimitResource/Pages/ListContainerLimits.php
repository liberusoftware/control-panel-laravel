<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerLimitResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerLimitResource;

final class ListContainerLimits extends ListRecords
{
    protected static string $resource = ContainerLimitResource::class;
}
