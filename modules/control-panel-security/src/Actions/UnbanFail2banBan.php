<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\Fail2banBan;

final class UnbanFail2banBan
{
    public function execute(Fail2banBan $ban): Fail2banBan
    {
        if ($ban->unbanned_at !== null) {
            throw ValidationException::withMessages(['ban' => 'The IP address is already unbanned.']);
        }
        $ban->forceFill(['unbanned_at' => now()])->save();

        return $ban->refresh();
    }
}
