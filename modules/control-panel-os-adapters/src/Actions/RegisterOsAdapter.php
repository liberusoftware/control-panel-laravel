<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;

final class RegisterOsAdapter
{
    public function execute(array $attributes): OsAdapter
    {
        return OsAdapter::query()->updateOrCreate(['team_id' => $attributes['team_id'] ?? null, 'node_id' => $attributes['node_id']], array_merge(['status' => 'available', 'capabilities' => [], 'metadata' => []], $attributes));
    }
}
