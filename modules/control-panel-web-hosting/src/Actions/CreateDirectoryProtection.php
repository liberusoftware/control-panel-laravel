<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\DirectoryProtection;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class CreateDirectoryProtection
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): DirectoryProtection
    {
        $path = trim((string) ($attributes['directory_path'] ?? ''));
        if ($path === '' || ! str_starts_with($path, '/')) {
            throw ValidationException::withMessages(['directory_path' => 'The directory path must be absolute.']);
        }

        return DirectoryProtection::query()->updateOrCreate(
            ['domain_id' => $domain->getKey(), 'directory_path' => $path],
            ['id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'auth_name' => trim((string) ($attributes['auth_name'] ?? 'Protected Area')) ?: 'Protected Area', 'htpasswd_file_path' => trim((string) ($attributes['htpasswd_file_path'] ?? $path.'/.htpasswd')), 'active' => (bool) ($attributes['active'] ?? true)],
        );
    }
}
