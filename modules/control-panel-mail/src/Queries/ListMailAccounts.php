<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Mail\Models\MailAccount;

final class ListMailAccounts
{
    public function execute(?string $teamId, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        return MailAccount::query()->where('team_id', $teamId)->when(trim($search) !== '', fn ($query) => $query->where('address', 'like', '%'.trim($search).'%'))
            ->latest()->paginate(min(max($perPage, 1), 100));
    }
}
