<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerLifecycleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerLifecycleResource;

final class ListContainerLifecycles extends ListRecords
{
    protected static string $resource = ContainerLifecycleResource::class;
}
