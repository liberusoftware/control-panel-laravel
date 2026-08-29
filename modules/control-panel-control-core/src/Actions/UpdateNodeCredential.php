<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;

final class UpdateNodeCredential
{
    /** @param array<string, mixed> $attributes */
    public function execute(NodeCredential $credential, array $attributes): NodeCredential
    {
        if ($credential->status !== CredentialStatus::Active) {
            throw ValidationException::withMessages(['credential' => 'Only active credentials can be updated.']);
        }

        $name = trim((string) ($attributes['name'] ?? $credential->name));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A credential name is required.']);
        }

        $credential->forceFill([
            'name' => $name,
            'username' => $attributes['username'] ?? $credential->username,
            'expires_at' => $attributes['expires_at'] ?? $credential->expires_at,
            'metadata' => $attributes['metadata'] ?? $credential->metadata,
        ])->save();

        return $credential->refresh();
    }
}
