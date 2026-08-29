<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailDomain;

final class RegisterMailDomain
{
    public function execute(array $attributes): MailDomain
    {
        $teamId = $attributes['team_id'] ?? null;
        $domain = mb_strtolower(trim((string) ($attributes['domain'] ?? '')));

        if ($teamId === null || trim((string) $teamId) === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }
        if ($domain === '' || mb_strlen($domain) > 253 || ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw ValidationException::withMessages(['domain' => 'A valid mail domain is required.']);
        }

        return MailDomain::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => (string) $teamId,
            'domain' => $domain,
            'status' => 'active',
            'dkim' => $attributes['dkim'] ?? [],
            'spf' => $attributes['spf'] ?? [],
            'dmarc' => $attributes['dmarc'] ?? [],
        ]);
    }
}
