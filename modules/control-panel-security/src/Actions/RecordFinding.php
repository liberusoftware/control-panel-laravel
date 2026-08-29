<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\SecurityFinding;

final class RecordFinding
{
    public function execute(array $attributes): SecurityFinding
    {
        $teamId = trim((string) ($attributes['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team context is required.']);
        }

        return SecurityFinding::query()->updateOrCreate(['team_id' => $teamId, 'code' => $attributes['code'], 'subject_id' => $attributes['subject_id']], array_merge(['status' => 'open', 'evidence' => []], $attributes, ['team_id' => $teamId]));
    }
}
