<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;

final class ListClusters
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Cluster::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
