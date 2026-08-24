<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Monitoring\Models\Monitor;

final class ListMonitors
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Monitor::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
