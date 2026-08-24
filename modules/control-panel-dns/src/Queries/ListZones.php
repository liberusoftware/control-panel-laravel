<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Dns\Models\Zone;

final class ListZones
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Zone::query()->with('records')->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
