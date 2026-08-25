<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\ContainersFilament\Resources\WorkloadResource;

final class CreateWorkload extends CreateRecord
{
    protected static string $resource = WorkloadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
