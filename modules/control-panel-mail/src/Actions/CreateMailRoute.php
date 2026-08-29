<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailRoute;

final class CreateMailRoute
{
    public function execute(array $attributes): MailRoute
    {
        $teamId = $attributes['team_id'] ?? null;
        $domain = strtolower(trim((string) ($attributes['domain'] ?? '')));
        $sourcePattern = trim((string) ($attributes['source_pattern'] ?? ''));
        $destination = trim((string) ($attributes['destination'] ?? ''));

        if ($teamId === null || trim((string) $teamId) === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        if ($domain === '' || mb_strlen($domain) > 253 || ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || $sourcePattern === '' || ! filter_var($destination, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['route' => 'A valid domain, source pattern, and destination are required.']);
        }

        return MailRoute::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => (string) $teamId,
            'domain' => $domain,
            'source_pattern' => $sourcePattern,
            'destination' => $destination,
            'priority' => max((int) ($attributes['priority'] ?? 100), 0),
            'active' => (bool) ($attributes['active'] ?? true),
        ]);
    }
}
