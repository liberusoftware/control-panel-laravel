<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;

final class UpdateApiCredential
{
    /** @param array<string, mixed> $attributes */
    public function execute(ApiCredential $credential, array $attributes): ApiCredential
    {
        if ($credential->status !== 'active') {
            throw ValidationException::withMessages(['credential' => 'Only active credentials can be updated.']);
        }

        $name = trim((string) ($attributes['name'] ?? $credential->name));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A credential name is required.']);
        }

        $credential->forceFill(['name' => $name, 'scopes' => $attributes['scopes'] ?? $credential->scopes, 'expires_at' => $attributes['expires_at'] ?? $credential->expires_at])->save();

        return $credential->refresh();
    }
}
