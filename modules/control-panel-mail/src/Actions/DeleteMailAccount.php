<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Liberu\ControlPanel\Mail\Models\MailAccount;

final class DeleteMailAccount
{
    public function execute(MailAccount $account): void
    {
        $account->delete();
    }
}
