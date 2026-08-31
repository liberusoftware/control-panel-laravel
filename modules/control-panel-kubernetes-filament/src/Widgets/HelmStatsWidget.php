<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Kubernetes\Models\HelmRelease;

final class HelmStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $releases = HelmRelease::query()->where('team_id', $teamId);

        return [
            Stat::make('Total releases', (string) (clone $releases)->count()),
            Stat::make('Deployed', (string) $this->countByStatus($releases, 'deployed')),
            Stat::make('Failed', (string) $this->countByStatus($releases, 'failed')),
            Stat::make('Pending', (string) $this->countByStatus($releases, 'pending')),
        ];
    }

    private function countByStatus(Builder $releases, string $status): int
    {
        return (clone $releases)->where('status', $status)->count();
    }
}
