<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Databases\Models\DatabaseBackup;

final class ListDatabaseBackups
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return DatabaseBackup::query()->with('database')->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
