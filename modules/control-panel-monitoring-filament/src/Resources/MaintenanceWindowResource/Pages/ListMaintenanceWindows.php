<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource;

final class ListMaintenanceWindows extends ListRecords
{
    protected static string $resource = MaintenanceWindowResource::class;
}
