<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\ControlPanel\Certificates\Models\Certificate;

final class ListCertificates
{
    public function execute(?string $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Certificate::query()->where('team_id', $teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
