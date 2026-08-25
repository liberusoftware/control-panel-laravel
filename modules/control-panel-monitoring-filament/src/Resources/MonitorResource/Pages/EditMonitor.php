<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource;

final class EditMonitor extends EditRecord
{
    protected static string $resource = MonitorResource::class;
}
