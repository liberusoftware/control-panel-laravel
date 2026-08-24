<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Containers\Models\Workload;

final class ListWorkloads
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Workload::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
