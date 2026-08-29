<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\SecurityFilament\Resources\HardeningControlResource;

final class CreateHardeningControl extends CreateRecord
{
    protected static string $resource = HardeningControlResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
