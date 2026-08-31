<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerVolumeResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerVolumeResource;

final class ListContainerVolumes extends ListRecords
{
    protected static string $resource = ContainerVolumeResource::class;
}
