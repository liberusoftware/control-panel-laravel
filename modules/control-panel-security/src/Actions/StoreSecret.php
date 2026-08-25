<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\SecretRecord;

final class StoreSecret
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): SecretRecord
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $value = (string) ($attributes['value'] ?? '');
        if ($name === '' || $value === '') {
            throw ValidationException::withMessages(['secret' => 'A secret name and value are required.']);
        }

        return SecretRecord::query()->updateOrCreate(
            ['team_id' => $attributes['team_id'], 'name' => $name],
            ['id' => (string) Str::uuid(), 'purpose' => $attributes['purpose'] ?? null, 'value' => $value, 'version' => ((int) ($attributes['version'] ?? 0)) + 1, 'status' => 'active', 'expires_at' => $attributes['expires_at'] ?? null, 'rotated_at' => now()],
        );
    }
}
