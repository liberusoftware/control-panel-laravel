<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class ListDomains
{
    public function execute(?string $teamId = null, int $perPage = 25): LengthAwarePaginator
    {
        return Domain::query()
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->with('virtualHosts')
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }
}
