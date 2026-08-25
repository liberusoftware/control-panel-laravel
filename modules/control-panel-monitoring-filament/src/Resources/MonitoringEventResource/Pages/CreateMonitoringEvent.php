<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitoringEventResource;

final class CreateMonitoringEvent extends CreateRecord
{
    protected static string $resource = MonitoringEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
