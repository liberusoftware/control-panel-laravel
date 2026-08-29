<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase as CreateDatabaseAction;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource;

final class CreateDatabase extends CreateRecord
{
    protected static string $resource = DatabaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateDatabaseAction::class)->execute($data);
    }
}
