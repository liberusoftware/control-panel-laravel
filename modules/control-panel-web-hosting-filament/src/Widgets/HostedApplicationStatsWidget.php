<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Liberu\ControlPanel\WebHosting\Queries\ApplicationStatistics;

final class HostedApplicationStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $statistics = app(ApplicationStatistics::class)->execute((string) $teamId);

        return [
            Stat::make('Hosted applications', (string) $statistics['total_applications']),
            Stat::make('Installed applications', (string) $statistics['installed_applications']),
            Stat::make('Uptime', $statistics['uptime_percentage'] === null ? '—' : $statistics['uptime_percentage'].'%'),
            Stat::make('Health checks', (string) $statistics['total_checks']),
        ];
    }
}
