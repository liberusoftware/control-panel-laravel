<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHostingFilament\Resources\VirtualHostResource;

final class ListVirtualHosts extends ListRecords
{
    protected static string $resource = VirtualHostResource::class;
}
