<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Liberu\ControlPanel\Accounts\Models\AccountDelegation;

final class RevokeDelegation
{
    public function execute(AccountDelegation $delegation): AccountDelegation
    {
        $delegation->forceFill(['active' => false])->save();

        return $delegation->refresh();
    }
}
