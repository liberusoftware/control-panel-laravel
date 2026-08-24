<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Liberu\ControlPanel\Security\Models\SecurityFinding;

final class RecordFinding
{
    public function execute(array $attributes): SecurityFinding
    {
        return SecurityFinding::query()->updateOrCreate(['team_id' => $attributes['team_id'] ?? null, 'code' => $attributes['code'], 'subject_id' => $attributes['subject_id']], array_merge(['status' => 'open', 'evidence' => []], $attributes));
    }
}
