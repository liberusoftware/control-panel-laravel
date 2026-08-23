<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Services;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Accounts\Models\Account;

final class QuotaGuard
{
    /** @param array<string, int> $usage */
    public function assertWithinQuota(Account $account, array $usage): void
    {
        foreach ($usage as $resource => $value) {
            $limit = $account->quota_overrides[$resource] ?? null;
            if ($limit !== null && ((int) $value < 0 || (int) $value > (int) $limit)) {
                throw ValidationException::withMessages([
                    $resource => "The {$resource} quota has been exceeded.",
                ]);
            }
        }
    }
}
