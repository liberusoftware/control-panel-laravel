<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\ControlCore\Models\AuditEntry;

final class ListAuditEntries
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return AuditEntry::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->latest()->paginate(min(max($perPage, 1), 100));
    }
}
