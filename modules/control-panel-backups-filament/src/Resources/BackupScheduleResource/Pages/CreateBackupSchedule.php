<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\Backups\Actions\CreateSchedule;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource;

final class CreateBackupSchedule extends CreateRecord
{
    protected static string $resource = BackupScheduleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $policy = BackupPolicy::query()->whereKey($data['policy_id'])->where('team_id', $teamId)->firstOrFail();

        return app(CreateSchedule::class)->execute($policy, $data['cron'], $data['timezone'] ?? 'UTC');
    }
}
