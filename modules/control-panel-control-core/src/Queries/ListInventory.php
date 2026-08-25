<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\ControlCore\Models\InventoryRecord;

final class ListInventory
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return InventoryRecord::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->latest('observed_at')->paginate(min(max($perPage, 1), 100));
    }
}
