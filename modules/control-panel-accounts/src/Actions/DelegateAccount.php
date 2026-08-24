<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\Account;
use Liberu\ControlPanel\Accounts\Models\AccountDelegation;

final class DelegateAccount
{
    /** @param array<string, mixed> $attributes */
    public function execute(Account $account, array $attributes): AccountDelegation
    {
        $delegateId = trim((string) ($attributes['delegate_id'] ?? ''));
        if ($delegateId === '' || $delegateId === (string) $account->owner_id) {
            throw ValidationException::withMessages(['delegate_id' => 'A different delegate is required.']);
        }

        return AccountDelegation::query()->updateOrCreate(
            ['account_id' => $account->getKey(), 'delegate_id' => $delegateId],
            ['id' => (string) Str::uuid(), 'team_id' => $account->team_id, 'permissions' => $attributes['permissions'] ?? [], 'expires_at' => $attributes['expires_at'] ?? null, 'active' => true],
        );
    }
}
