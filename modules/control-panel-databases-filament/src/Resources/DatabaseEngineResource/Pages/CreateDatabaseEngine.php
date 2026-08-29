<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource;

final class CreateDatabaseEngine extends CreateRecord
{
    protected static string $resource = DatabaseEngineResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }
}
