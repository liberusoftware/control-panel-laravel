<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\UptimeCheckResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\UptimeCheckResource;

final class ListUptimeChecks extends ListRecords
{
    protected static string $resource = UptimeCheckResource::class;
}
