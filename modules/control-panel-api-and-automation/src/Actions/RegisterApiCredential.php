<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;

final class RegisterApiCredential
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): ApiCredential
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $secret = trim((string) ($attributes['secret'] ?? Str::random(48)));
        if ($name === '' || $secret === '') {
            throw ValidationException::withMessages(['credential' => 'A credential name and secret are required.']);
        }

        return ApiCredential::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'name' => $name, 'scopes' => array_values(array_unique($attributes['scopes'] ?? [])), 'secret' => $secret, 'status' => 'active', 'expires_at' => $attributes['expires_at'] ?? null]);
    }
}
