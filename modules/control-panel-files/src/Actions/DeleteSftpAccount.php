<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Liberu\ControlPanel\Files\Models\SftpAccount;

final class DeleteSftpAccount
{
    public function execute(SftpAccount $account): void
    {
        $account->delete();
    }
}
