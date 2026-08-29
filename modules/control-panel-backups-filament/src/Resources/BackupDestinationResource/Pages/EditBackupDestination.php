<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Backups\Actions\UpdateDestination;
use Liberu\ControlPanel\Backups\Models\BackupDestination;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource;

final class EditBackupDestination extends EditRecord
{
    protected static string $resource = BackupDestinationResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BackupDestination $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateDestination::class)->execute($record, $data);
    }
}
