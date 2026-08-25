<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\MonitoringFilament\Resources\MonitorResource;

final class CreateMonitor extends CreateRecord
{
    protected static string $resource = MonitorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
