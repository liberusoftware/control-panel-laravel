<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\ControlPanel\WebHosting\Models\ResourceUsage;

final class ListResourceUsage
{
    /** @return Collection<int, ResourceUsage> */
    public function execute(string|int $teamId, ?string $domainId = null, int $months = 12): Collection
    {
        $months = min(max($months, 1), 60);

        return ResourceUsage::query()
            ->where('team_id', $teamId)
            ->when($domainId !== null, fn ($query) => $query->forDomain($domainId))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit($months)
            ->get();
    }
}
