<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Liberu\ControlPanel\Mail\Models\MailAccount;

final class CreateMailAccount
{
    public function execute(array $attributes): MailAccount
    {
        return MailAccount::query()->create(array_merge(['status' => 'active', 'quota_bytes' => 0, 'settings' => []], $attributes));
    }
}
