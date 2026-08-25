<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\ControlCore\Models\Node;

final class ListNodes
{
    public function execute(?string $teamId = null, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return Node::query()
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->when(trim($search) !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.trim($search).'%')->orWhere('hostname', 'like', '%'.trim($search).'%')))
            ->with('capabilities')
            ->latest()
            ->paginate($perPage);
    }
}
