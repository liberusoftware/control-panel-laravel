<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\ApplicationMetric;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;

final class RecordApplicationMetric
{
    /** @param array<string, mixed> $attributes */
    public function execute(HostedApplication $application, array $attributes): ApplicationMetric
    {
        if (($attributes['team_id'] ?? $application->team_id) !== $application->team_id) {
            throw ValidationException::withMessages(['application' => 'The application does not belong to this team.']);
        }

        return ApplicationMetric::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $application->team_id,
            'application_id' => $application->getKey(),
            'response_time_ms' => max(0, (int) ($attributes['response_time_ms'] ?? 0)),
            'status_code' => max(0, (int) ($attributes['status_code'] ?? 0)),
            'healthy' => (bool) ($attributes['healthy'] ?? false),
            'checked_at' => $attributes['checked_at'] ?? now(),
            'details' => $attributes['details'] ?? [],
        ]);
    }
}
