<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailAccount;

final class UpdateMailAccount
{
    /** @param array<string, mixed> $attributes */
    public function execute(MailAccount $account, array $attributes): MailAccount
    {
        $domain = mb_strtolower(trim((string) ($attributes['domain'] ?? $account->domain)));
        $address = mb_strtolower(trim((string) ($attributes['address'] ?? $account->address)));
        $quota = (int) ($attributes['quota_bytes'] ?? $account->quota_bytes);

        if ($domain === '' || ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw ValidationException::withMessages(['domain' => 'A valid mail domain is required.']);
        }

        $mailbox = str_contains($address, '@') ? $address : $address.'@'.$domain;
        if (! filter_var($mailbox, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['address' => 'A valid mailbox address is required.']);
        }

        if ($quota < 0) {
            throw ValidationException::withMessages(['quota_bytes' => 'The mailbox quota cannot be negative.']);
        }

        $account->forceFill(['domain' => $domain, 'address' => $mailbox, 'quota_bytes' => $quota])->save();

        return $account->refresh();
    }
}
