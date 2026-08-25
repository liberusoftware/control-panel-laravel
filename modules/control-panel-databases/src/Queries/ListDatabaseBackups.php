<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Databases\Models\DatabaseBackup;

final class ListDatabaseBackups
{
    public function execute(?string $teamId, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        return DatabaseBackup::query()->with('database')->where('team_id', $teamId)->when(trim($search) !== '', fn ($query) => $query->where('status', 'like', '%'.trim($search).'%'))
            ->latest()->paginate(min(max($perPage, 1), 100));
    }
}
