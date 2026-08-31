<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Containers\Models\Workload;

final class RegisterWorkload
{
    public function execute(array $attributes): Workload
    {
        $teamId = trim((string) ($attributes['team_id'] ?? ''));
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A workload name is required.']);
        }

        return Workload::query()->updateOrCreate(
            ['team_id' => $teamId, 'node_id' => $attributes['node_id'] ?? null, 'name' => $name],
            array_merge(['status' => 'defined', 'specification' => []], $attributes, ['team_id' => $teamId, 'name' => $name]),
        );
    }
}
