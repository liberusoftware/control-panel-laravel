<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\CronJob;

final class UpdateCronJob
{
    /** @param array<string, mixed> $attributes */
    public function execute(CronJob $job, array $attributes): CronJob
    {
        $name = trim((string) ($attributes['name'] ?? $job->name));
        $command = trim((string) ($attributes['command'] ?? $job->command));
        $schedule = trim((string) ($attributes['schedule'] ?? $job->schedule));
        if ($name === '' || $command === '') {
            throw ValidationException::withMessages(['name' => 'A job name and command are required.']);
        }
        app(CreateCronJob::class)->assertSchedule($schedule);
        $job->forceFill(['name' => $name, 'command' => $command, 'schedule' => $schedule, 'active' => $attributes['active'] ?? $job->active])->save();

        return $job->refresh();
    }
}
