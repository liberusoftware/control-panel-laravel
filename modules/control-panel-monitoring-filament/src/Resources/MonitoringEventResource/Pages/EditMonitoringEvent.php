<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource;

final class EditMonitoringEvent extends EditRecord
{
    protected static string $resource = MonitoringEventResource::class;
}
