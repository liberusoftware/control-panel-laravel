<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\HomeDirectory;

final class CreateHomeDirectory
{
    public function execute(array $attributes): HomeDirectory
    {
        $path = trim((string) ($attributes['path'] ?? ''));
        if ($path === '' || ! str_starts_with($path, '/')) {
            throw ValidationException::withMessages(['path' => 'A normalized absolute home-directory path is required.']);
        }

        return HomeDirectory::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'owner_id' => $attributes['owner_id'] ?? null, 'path' => rtrim($path, '/') ?: '/', 'disk' => $attributes['disk'] ?? 'local', 'mode' => (int) ($attributes['mode'] ?? 750), 'status' => 'active', 'metadata' => $attributes['metadata'] ?? []]);
    }
}
