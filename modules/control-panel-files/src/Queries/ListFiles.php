<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Files\Models\FileEntry;

final class ListFiles
{
    public function execute(?string $teamId, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        return FileEntry::query()
            ->where('team_id', $teamId)
            ->whereNot('status', 'deleted')
            ->when(trim($search) !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('path', 'like', '%'.trim($search).'%')
                    ->orWhere('mime_type', 'like', '%'.trim($search).'%');
            }))
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }
}
