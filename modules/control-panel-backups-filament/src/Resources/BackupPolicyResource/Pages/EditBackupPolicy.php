<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Backups\Actions\UpdatePolicy;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource;

final class EditBackupPolicy extends EditRecord
{
    protected static string $resource = BackupPolicyResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BackupPolicy $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdatePolicy::class)->execute($record, $data);
    }
}
