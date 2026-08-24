<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;

final class ListOsAdapters
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return OsAdapter::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
