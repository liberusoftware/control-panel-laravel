<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Dns\Models\Zone;

final class UpdateZone
{
    /** @param array<string, mixed> $attributes */
    public function execute(Zone $zone, array $attributes): Zone
    {
        $domain = strtolower(trim((string) ($attributes['domain'] ?? $zone->domain)));
        if ($domain === '' || filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw ValidationException::withMessages(['domain' => 'A valid DNS domain is required.']);
        }

        $exists = Zone::query()
            ->where('team_id', $zone->team_id)
            ->where('domain', $domain)
            ->where($zone->getKeyName(), '!=', $zone->getKey())
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['domain' => 'This DNS domain is already registered for the current team.']);
        }

        $zone->forceFill([
            'domain' => $domain,
            'provider' => $attributes['provider'] ?? $zone->provider,
            'dnssec_enabled' => array_key_exists('dnssec_enabled', $attributes) ? (bool) $attributes['dnssec_enabled'] : $zone->dnssec_enabled,
            'metadata' => $attributes['metadata'] ?? $zone->metadata,
        ])->save();

        return $zone->refresh();
    }
}
