<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Backups\Actions\UpdateSchedule;
use Liberu\ControlPanel\Backups\Models\BackupSchedule;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource;

final class EditBackupSchedule extends EditRecord
{
    protected static string $resource = BackupScheduleResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BackupSchedule $record */
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);

        return app(UpdateSchedule::class)->execute($record, $data);
    }
}
