<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Models\Account;

final class ActivateAccount
{
    public function execute(Account $account): Account
    {
        if ($account->status === AccountStatus::Archived) {
            throw ValidationException::withMessages(['account' => 'An archived account cannot be activated.']);
        }

        return DB::transaction(function () use ($account): Account {
            $account->forceFill(['status' => AccountStatus::Active, 'suspended_reason' => null, 'suspended_at' => null])->save();

            return $account->refresh();
        });
    }
}
