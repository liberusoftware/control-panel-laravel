<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Models\Account;

final class ArchiveAccount
{
    public function execute(Account $account): Account
    {
        if ($account->status === AccountStatus::Archived) {
            throw ValidationException::withMessages(['account' => 'The account is already archived.']);
        }

        return DB::transaction(function () use ($account): Account {
            $account->forceFill(['status' => AccountStatus::Archived])->save();

            return $account->refresh();
        });
    }
}
