<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;

final class ListSnapshots
{
    public function execute(?string $teamId, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        return BackupSnapshot::query()->with('policy')->where('team_id', $teamId)->when(trim($search) !== '', fn ($query) => $query->where('status', 'like', '%'.trim($search).'%'))
            ->latest()->paginate(min(max($perPage, 1), 100));
    }
}
