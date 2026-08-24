<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Liberu\ControlPanel\Containers\Models\Workload;

final class RegisterWorkload
{
    public function execute(array $attributes): Workload
    {
        return Workload::query()->updateOrCreate(['team_id' => $attributes['team_id'] ?? null, 'node_id' => $attributes['node_id'], 'name' => $attributes['name']], array_merge(['status' => 'defined', 'specification' => []], $attributes));
    }
}
