<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateApiCredential;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\ApiCredentialResource;

final class EditApiCredential extends EditRecord
{
    protected static string $resource = ApiCredentialResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ApiCredential $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateApiCredential::class)->execute($record, $data);
    }
}
