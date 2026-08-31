<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerNetworkResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerNetworkResource;

final class ListContainerNetworks extends ListRecords
{
    protected static string $resource = ContainerNetworkResource::class;
}
