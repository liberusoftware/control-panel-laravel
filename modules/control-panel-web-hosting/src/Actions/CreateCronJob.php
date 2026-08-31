<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\CronJob;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class CreateCronJob
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): CronJob
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $command = trim((string) ($attributes['command'] ?? ''));
        $schedule = trim((string) ($attributes['schedule'] ?? ''));
        if ($name === '' || $command === '') {
            throw ValidationException::withMessages(['name' => 'A job name and command are required.']);
        }
        $this->assertSchedule($schedule);

        return CronJob::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'domain_id' => $domain->getKey(),
            'name' => $name, 'command' => $command, 'schedule' => $schedule, 'active' => $attributes['active'] ?? true,
        ]);
    }

    public function assertSchedule(string $schedule): void
    {
        if (count(preg_split('/\s+/', $schedule, -1, PREG_SPLIT_NO_EMPTY) ?: []) !== 5 || ! preg_match('/^[0-9*\/,\-\s]+$/', $schedule)) {
            throw ValidationException::withMessages(['schedule' => 'A five-field cron schedule is required.']);
        }
    }
}
