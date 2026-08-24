<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\Account;

final class UpdateBranding
{
    /** @param array<string, mixed> $branding */
    public function execute(Account $account, array $branding): Account
    {
        if (isset($branding['logo_url']) && ! filter_var($branding['logo_url'], FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages(['logo_url' => 'The logo URL must be valid.']);
        }

        $account->forceFill(['brand' => $branding])->save();

        return $account->refresh();
    }
}
