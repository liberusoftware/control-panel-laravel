<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailRoute;

final class UpdateMailRoute
{
    /** @param array<string, mixed> $attributes */
    public function execute(MailRoute $route, array $attributes): MailRoute
    {
        $domain = strtolower(trim((string) ($attributes['domain'] ?? $route->domain)));
        $sourcePattern = trim((string) ($attributes['source_pattern'] ?? $route->source_pattern));
        $destination = trim((string) ($attributes['destination'] ?? $route->destination));
        $priority = (int) ($attributes['priority'] ?? $route->priority);

        if ($domain === '' || mb_strlen($domain) > 253 || ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || $sourcePattern === '' || ! filter_var($destination, FILTER_VALIDATE_EMAIL) || $priority < 0) {
            throw ValidationException::withMessages(['route' => 'A valid domain, source pattern, destination, and priority are required.']);
        }

        $route->forceFill(['domain' => $domain, 'source_pattern' => $sourcePattern, 'destination' => $destination, 'priority' => $priority, 'active' => $attributes['active'] ?? $route->active])->save();

        return $route->refresh();
    }
}
