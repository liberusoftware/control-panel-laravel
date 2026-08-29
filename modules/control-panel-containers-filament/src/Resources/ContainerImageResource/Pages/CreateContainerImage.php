<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\ContainersFilament\Resources\ContainerImageResource;

final class CreateContainerImage extends CreateRecord
{
    protected static string $resource = ContainerImageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
