<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\HostingPackage;

final class CreateHostingPackage
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): HostingPackage
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A package name is required.']);
        }

        return HostingPackage::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'name' => $name, 'limits' => $attributes['limits'] ?? [], 'features' => $attributes['features'] ?? [], 'active' => $attributes['active'] ?? true]);
    }
}
