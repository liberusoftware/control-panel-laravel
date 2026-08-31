<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerSecretResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerSecretResource;

final class ListContainerSecrets extends ListRecords
{
    protected static string $resource = ContainerSecretResource::class;
}
