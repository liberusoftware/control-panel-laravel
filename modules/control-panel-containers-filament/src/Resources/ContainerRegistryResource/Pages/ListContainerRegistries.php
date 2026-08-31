<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerRegistryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerRegistryResource;

final class ListContainerRegistries extends ListRecords
{
    protected static string $resource = ContainerRegistryResource::class;
}
