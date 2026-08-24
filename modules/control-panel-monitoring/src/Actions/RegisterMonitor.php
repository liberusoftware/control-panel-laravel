<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Monitoring\Actions;

use Liberu\ControlPanel\Monitoring\Models\Monitor;

final class RegisterMonitor
{
    public function execute(array $attributes): Monitor
    {
        return Monitor::query()->updateOrCreate(['team_id' => $attributes['team_id'] ?? null, 'subject_id' => $attributes['subject_id'], 'name' => $attributes['name']], array_merge(['status' => 'unknown', 'metrics' => []], $attributes));
    }
}
