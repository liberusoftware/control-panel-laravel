<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\HotlinkProtection;

final class ConfigureHotlinkProtection
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): HotlinkProtection
    {
        if (isset($attributes['team_id']) && (string) $attributes['team_id'] !== (string) $domain->team_id) {
            throw ValidationException::withMessages(['domain' => 'The domain does not belong to this team.']);
        }

        $allowedDomains = array_values(array_filter(array_map('trim', (array) ($attributes['allowed_domains'] ?? []))));
        $extensions = array_values(array_filter(array_map(static fn (mixed $extension): string => ltrim(trim((string) $extension), '.'), (array) ($attributes['protected_extensions'] ?? []))));
        $redirectUrl = $attributes['redirect_url'] ?? null;
        if ($redirectUrl !== null && $redirectUrl !== '' && filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages(['redirect_url' => 'The redirect URL must be valid.']);
        }

        return HotlinkProtection::query()->updateOrCreate(
            ['domain_id' => $domain->getKey()],
            ['id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'enabled' => (bool) ($attributes['enabled'] ?? false), 'allowed_domains' => $allowedDomains, 'protected_extensions' => $extensions, 'redirect_url' => $redirectUrl ?: null, 'allow_blank_referrer' => (bool) ($attributes['allow_blank_referrer'] ?? false)],
        );
    }
}
