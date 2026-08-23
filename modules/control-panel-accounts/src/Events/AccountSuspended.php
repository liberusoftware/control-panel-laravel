<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class AccountSuspended implements ShouldDispatchAfterCommit
{
    public function __construct(public string $accountId, public string $reason) {}
}
