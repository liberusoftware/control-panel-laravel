<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationDefinition;

final class ListAutomations
{
    public function execute(?string $teamId, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        return AutomationDefinition::query()->where('team_id', $teamId)->when(trim($search) !== '', fn ($query) => $query->where('name', 'like', '%'.trim($search).'%'))
            ->latest()->paginate(min(max($perPage, 1), 100));
    }
}
