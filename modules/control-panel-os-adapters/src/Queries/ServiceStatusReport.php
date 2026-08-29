<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Queries;

use Illuminate\Support\Collection;
use Liberu\ControlPanel\OsAdapters\Models\OsService;

final class ServiceStatusReport
{
    /** @return Collection<int, OsService> */
    public function all(string|int $teamId): Collection
    {
        return OsService::query()->where('team_id', $teamId)->orderBy('name')->get();
    }

    /** @return Collection<int, OsService> */
    public function missing(string|int $teamId): Collection
    {
        return $this->all($teamId)->whereIn('status', ['missing', 'not-installed'])->values();
    }

    /** @return Collection<int, OsService> */
    public function stopped(string|int $teamId): Collection
    {
        return $this->all($teamId)->filter(static fn (OsService $service): bool => ! in_array($service->status, ['running', 'active'], true))->values();
    }

    public function find(string|int $teamId, string $name): ?OsService
    {
        return OsService::query()->where('team_id', $teamId)->where('name', $name)->first();
    }
}
