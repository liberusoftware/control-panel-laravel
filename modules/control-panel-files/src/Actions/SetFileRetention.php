<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\FileRetention;

final class SetFileRetention
{
    public function execute(array $attributes): FileRetention
    {
        $until = $attributes['retention_until'] ?? null;
        if (empty($attributes['file_id']) || ! $until) {
            throw ValidationException::withMessages(['retention' => 'A file and retention expiry are required.']);
        }

        return FileRetention::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'file_id' => $attributes['file_id'], 'retention_until' => $until, 'policy' => $attributes['policy'] ?? 'standard', 'active' => true]);
    }
}
