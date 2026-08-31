<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\Fail2banSetting;

final class ConfigureFail2ban
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Fail2banSetting
    {
        $jail = trim((string) ($attributes['jail_name'] ?? ''));
        if ($jail === '' || ! preg_match('/^[A-Za-z0-9_.:-]+$/', $jail)) {
            throw ValidationException::withMessages(['jail_name' => 'A valid jail name is required.']);
        }
        $maxRetry = (int) ($attributes['max_retry'] ?? 5);
        $findTime = (int) ($attributes['find_time'] ?? 600);
        $banTime = (int) ($attributes['ban_time'] ?? 3600);
        if ($maxRetry < 1 || $findTime < 1 || $banTime < 1) {
            throw ValidationException::withMessages(['max_retry' => 'Fail2ban thresholds must be positive.']);
        }

        return Fail2banSetting::query()->updateOrCreate(
            ['team_id' => $attributes['team_id'] ?? null, 'jail_name' => $jail],
            ['id' => (string) Str::uuid(), 'enabled' => $attributes['enabled'] ?? true, 'max_retry' => $maxRetry, 'find_time' => $findTime, 'ban_time' => $banTime, 'whitelist_ips' => $attributes['whitelist_ips'] ?? []],
        );
    }
}
