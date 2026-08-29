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
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;
        $data['status'] = 'scheduled';

        return $data;
    }
}
