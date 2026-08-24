<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\FilePermission;

final class GrantFilePermission
{
    public function execute(array $attributes): FilePermission
    {
        $mode = (int) ($attributes['mode'] ?? 0);
        if ($mode < 0 || $mode > 777 || empty($attributes['subject_id'])) {
            throw ValidationException::withMessages(['permission' => 'A subject and a valid Unix permission mode are required.']);
        }

        return FilePermission::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'file_id' => $attributes['file_id'] ?? null, 'home_directory_id' => $attributes['home_directory_id'] ?? null, 'subject_id' => $attributes['subject_id'], 'subject_type' => $attributes['subject_type'] ?? 'account', 'mode' => $mode, 'recursive' => (bool) ($attributes['recursive'] ?? false)]);
    }
}
