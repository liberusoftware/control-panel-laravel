<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ApiAutomation\Actions\RegisterApiCredential;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource;

final class CreateApiCredential extends CreateRecord
{
    protected static string $resource = ApiCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        $data['team_id'] = auth()->user()?->current_team_id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(RegisterApiCredential::class)->execute($data);
    }
}
