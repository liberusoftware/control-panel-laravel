<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource\Pages;
use Filament\Resources\Pages\ListRecords; use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource;
final class ListMonitoringEvents extends ListRecords { protected static string $resource=MonitoringEventResource::class; }
