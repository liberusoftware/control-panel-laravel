<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\Backups\Models\BackupSchedule;

final class CreateSchedule
{
    public function execute(BackupPolicy $policy, string $cron, string $timezone = 'UTC'): BackupSchedule
    {
        $cron = trim($cron);
        if ($cron === '' || count(preg_split('/\s+/', $cron)) !== 5) {
            throw ValidationException::withMessages(['cron' => 'A five-field cron schedule is required.']);
        }

        return BackupSchedule::query()->create(['id' => (string) Str::uuid(), 'team_id' => $policy->team_id, 'policy_id' => $policy->getKey(), 'cron' => $cron, 'timezone' => $timezone, 'active' => true]);
    }
}
