<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Liberu\ControlPanel\WebHosting\Models\CronExecution;
use Liberu\ControlPanel\WebHosting\Models\CronJob;

final class RecordCronExecution
{
    /** @param array<string, mixed> $attributes */
    public function execute(CronJob $job, array $attributes): CronExecution
    {
        $execution = CronExecution::query()->create([
            'id' => (string) Str::uuid(), 'cron_job_id' => $job->getKey(),
            'started_at' => $attributes['started_at'] ?? now(), 'finished_at' => $attributes['finished_at'] ?? null,
            'exit_code' => $attributes['exit_code'] ?? null, 'output' => $attributes['output'] ?? null,
            'error_output' => $attributes['error_output'] ?? null, 'duration' => $attributes['duration'] ?? null,
        ]);
        $job->forceFill([
            'last_run_at' => $execution->finished_at ?? $execution->started_at,
            'output' => $execution->output, 'error_output' => $execution->error_output,
        ])->save();

        return $execution;
    }
}
