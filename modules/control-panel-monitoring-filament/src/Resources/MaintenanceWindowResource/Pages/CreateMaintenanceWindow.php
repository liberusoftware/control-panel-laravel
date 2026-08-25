<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\MonitoringFilament\Resources\MaintenanceWindowResource;

final class CreateMaintenanceWindow extends CreateRecord
{
    protected static string $resource = MaintenanceWindowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;
        $data['status'] = 'scheduled';

        return $data;
    }
}
