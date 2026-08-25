<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;

final class ListGitDeployments
{
    public function execute(string|int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return GitDeployment::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
