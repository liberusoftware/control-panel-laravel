<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\Redirect;

final class CreateRedirect
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): Redirect
    {
        $source = trim((string) ($attributes['source'] ?? ''));
        $destination = trim((string) ($attributes['destination'] ?? ''));
        $code = (int) ($attributes['status_code'] ?? 301);
        if ($source === '' || $destination === '' || ! in_array($code, [301, 302, 307, 308], true)) {
            throw ValidationException::withMessages(['redirect' => 'A source, destination, and supported redirect status are required.']);
        }

        return Redirect::query()->create(['id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'domain_id' => $domain->getKey(), 'source' => $source, 'destination' => $destination, 'status_code' => $code, 'active' => (bool) ($attributes['active'] ?? true), 'source_path' => $source, 'destination_url' => $destination, 'redirect_type' => (string) $code, 'match_query_string' => (bool) ($attributes['match_query_string'] ?? false), 'is_regex' => (bool) ($attributes['is_regex'] ?? false), 'priority' => (int) ($attributes['priority'] ?? 100)]);
    }
}
