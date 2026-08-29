<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource;

final class CreateNodeCredential extends CreateRecord
{
    protected static string $resource = NodeCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()->current_team_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(RegisterNodeCredential::class)->execute($data);
    }
}
