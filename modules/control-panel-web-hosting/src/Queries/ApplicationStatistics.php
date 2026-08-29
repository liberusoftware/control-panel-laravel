<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Queries;

use Illuminate\Support\Carbon;
use Liberu\ControlPanel\WebHosting\Models\ApplicationMetric;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;

final class ApplicationStatistics
{
    /** @return array<string, int|float|null> */
    public function execute(string|int $teamId, int $days = 30): array
    {
        $days = min(max($days, 1), 365);
        $since = Carbon::now()->subDays($days);
        $applications = HostedApplication::query()->where('team_id', $teamId);
        $metrics = ApplicationMetric::query()->where('team_id', $teamId)->where('checked_at', '>=', $since);
        $totalChecks = (clone $metrics)->count();
        $healthyChecks = (clone $metrics)->where('healthy', true)->count();

        return [
            'total_applications' => (clone $applications)->count(),
            'installed_applications' => (clone $applications)->where('status', 'installed')->count(),
            'total_checks' => $totalChecks,
            'healthy_checks' => $healthyChecks,
            'uptime_percentage' => $totalChecks === 0 ? null : round(($healthyChecks / $totalChecks) * 100, 2),
            'average_response_time' => $totalChecks === 0 ? null : round((float) (clone $metrics)->avg('response_time_ms'), 2),
            'days' => $days,
        ];
    }
}
