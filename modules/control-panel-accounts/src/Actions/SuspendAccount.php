<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Enums\AccountStatus;
use Liberu\ControlPanel\Accounts\Events\AccountSuspended;
use Liberu\ControlPanel\Accounts\Models\Account;

final readonly class SuspendAccount
{
    public function __construct(private Dispatcher $events) {}

    public function execute(Account $account, string $reason): Account
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A suspension reason is required.']);
        }

        return DB::transaction(function () use ($account, $reason): Account {
            $account->forceFill([
                'status' => AccountStatus::Suspended,
                'suspended_reason' => $reason,
                'suspended_at' => now(),
            ])->save();
            $this->events->dispatch(new AccountSuspended((string) $account->getKey(), $reason));

            return $account->refresh();
        });
    }
}
