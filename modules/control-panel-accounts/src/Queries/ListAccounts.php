<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Accounts\Models\Account;

final class ListAccounts
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Account::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->latest()->paginate(min(max($perPage, 1), 100));
    }
}
