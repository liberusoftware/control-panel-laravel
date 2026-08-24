<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource;

final class ListMonitors extends ListRecords
{
    protected static string $resource = MonitorResource::class;
}
