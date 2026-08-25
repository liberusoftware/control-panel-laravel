<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHostingFilament\Resources\HostingLogResource;

final class ListHostingLogs extends ListRecords
{
    protected static string $resource = HostingLogResource::class;
}
