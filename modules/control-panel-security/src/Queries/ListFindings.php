<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Security\Models\SecurityFinding;

final class ListFindings
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return SecurityFinding::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
