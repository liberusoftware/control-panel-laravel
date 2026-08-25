<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHostingFilament\Resources\PhpConfigurationResource;

final class ListPhpConfigurations extends ListRecords
{
    protected static string $resource = PhpConfigurationResource::class;
}
