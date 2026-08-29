<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ControlCore\Actions\UpdateNodeCredential;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Liberu\ControlPanel\ControlCoreFilament\Resources\NodeCredentialResource;

final class EditNodeCredential extends EditRecord
{
    protected static string $resource = NodeCredentialResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var NodeCredential $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()->current_team_id, 404);

        return app(UpdateNodeCredential::class)->execute($record, $data);
    }
}
