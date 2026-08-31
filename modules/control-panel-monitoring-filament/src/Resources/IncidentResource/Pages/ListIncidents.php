<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\IncidentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\MonitoringFilament\Resources\IncidentResource;

final class ListIncidents extends ListRecords
{
    protected static string $resource = IncidentResource::class;
}
