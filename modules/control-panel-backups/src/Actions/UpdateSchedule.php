<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupSchedule;

final class UpdateSchedule
{
    /** @param array<string, mixed> $attributes */
    public function execute(BackupSchedule $schedule, array $attributes): BackupSchedule
    {
        $cron = trim((string) ($attributes['cron'] ?? $schedule->cron));
        $timezone = trim((string) ($attributes['timezone'] ?? $schedule->timezone));
        if ($cron === '' || count(preg_split('/\s+/', $cron)) !== 5) {
            throw ValidationException::withMessages(['cron' => 'A five-field cron schedule is required.']);
        }
        try {
            new \DateTimeZone($timezone);
        } catch (\DateTimeZoneInvalidTimeZoneException) {
            throw ValidationException::withMessages(['timezone' => 'A valid timezone is required.']);
        }

        $schedule->forceFill(['cron' => $cron, 'timezone' => $timezone, 'active' => $attributes['active'] ?? $schedule->active])->save();

        return $schedule->refresh();
    }
}
