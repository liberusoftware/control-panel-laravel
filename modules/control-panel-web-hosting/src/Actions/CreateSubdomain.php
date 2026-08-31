<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\Subdomain;

final class CreateSubdomain
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): Subdomain
    {
        $subdomain = strtolower(trim((string) ($attributes['subdomain'] ?? '')));
        $documentRoot = trim((string) ($attributes['document_root'] ?? ''));
        $redirectType = $attributes['redirect_type'] ?? null;
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            throw ValidationException::withMessages(['subdomain' => 'The subdomain contains an invalid label.']);
        }
        if ($documentRoot === '' || ! str_starts_with($documentRoot, '/')) {
            throw ValidationException::withMessages(['document_root' => 'The document root must be an absolute path.']);
        }
        if ($redirectType !== null && ! in_array((int) $redirectType, [301, 302], true)) {
            throw ValidationException::withMessages(['redirect_type' => 'The redirect type must be 301 or 302.']);
        }

        return Subdomain::query()->updateOrCreate(
            ['domain_id' => $domain->getKey(), 'subdomain' => $subdomain],
            ['id' => (string) Str::uuid(), 'document_root' => $documentRoot, 'php_version' => $attributes['php_version'] ?? null, 'active' => $attributes['active'] ?? true, 'redirect_url' => $attributes['redirect_url'] ?? null, 'redirect_type' => $redirectType],
        );
    }
}
