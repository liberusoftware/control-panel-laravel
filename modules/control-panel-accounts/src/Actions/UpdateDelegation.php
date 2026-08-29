<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;

final class UpdateDelegation
{
    /** @param array<string, mixed> $attributes */
    public function execute(AccountDelegation $delegation, array $attributes): AccountDelegation
    {
        $delegateId = trim((string) ($attributes['delegate_id'] ?? $delegation->delegate_id));
        $ownerId = (string) ($delegation->account?->owner_id ?? '');

        if ($delegateId === '' || $delegateId === $ownerId) {
            throw ValidationException::withMessages(['delegate_id' => 'A different delegate is required.']);
        }

        $delegation->forceFill([
            'delegate_id' => $delegateId,
            'permissions' => $attributes['permissions'] ?? $delegation->permissions,
            'expires_at' => $attributes['expires_at'] ?? $delegation->expires_at,
            'active' => $attributes['active'] ?? $delegation->active,
        ])->save();

        return $delegation->refresh();
    }
}
