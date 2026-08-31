<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\CustomErrorPage;
use Liberu\ControlPanel\WebHosting\Models\Domain;

final class SaveCustomErrorPage
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): CustomErrorPage
    {
        $code = (int) ($attributes['error_code'] ?? 0);
        $content = $attributes['custom_content'] ?? null;
        $filePath = $attributes['custom_file_path'] ?? null;
        if ($code < 100 || $code > 599 || (($content === null || trim((string) $content) === '') && ($filePath === null || trim((string) $filePath) === ''))) {
            throw ValidationException::withMessages(['error_page' => 'An HTTP status between 100 and 599 and custom content or a file path are required.']);
        }
        if ($filePath !== null && $filePath !== '' && ! str_starts_with((string) $filePath, '/')) {
            throw ValidationException::withMessages(['custom_file_path' => 'The custom file path must be absolute.']);
        }

        return CustomErrorPage::query()->updateOrCreate(
            ['domain_id' => $domain->getKey(), 'error_code' => $code],
            ['id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'custom_content' => $content, 'custom_file_path' => $filePath ?: null, 'active' => (bool) ($attributes['active'] ?? true)],
        );
    }
}
