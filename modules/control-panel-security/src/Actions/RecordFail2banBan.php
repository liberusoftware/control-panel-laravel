<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Security\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Security\Models\Fail2banBan;
use Liberu\ControlPanel\Security\Models\Fail2banSetting;

final class RecordFail2banBan
{
    /** @param array<string, mixed> $attributes */
    public function execute(Fail2banSetting $setting, array $attributes): Fail2banBan
    {
        $ip = trim((string) ($attributes['ip_address'] ?? ''));
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw ValidationException::withMessages(['ip_address' => 'A valid IP address is required.']);
        }
        if (in_array($ip, $setting->whitelist_ips ?? [], true)) {
            throw ValidationException::withMessages(['ip_address' => 'Whitelisted IP addresses cannot be banned.']);
        }

        return Fail2banBan::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $setting->team_id, 'jail_name' => $setting->jail_name,
            'ip_address' => $ip, 'banned_at' => $attributes['banned_at'] ?? now(), 'ban_count' => $attributes['ban_count'] ?? 1, 'reason' => $attributes['reason'] ?? null,
        ]);
    }
}
