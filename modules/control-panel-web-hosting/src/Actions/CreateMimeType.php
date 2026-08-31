<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\MimeType;

final class CreateMimeType
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes): MimeType
    {
        $extension = trim((string) ($attributes['extension'] ?? ''));
        $mimeType = trim((string) ($attributes['mime_type'] ?? ''));
        if (! preg_match('/^\.[a-z0-9][a-z0-9._-]{0,31}$/i', $extension)) {
            throw ValidationException::withMessages(['extension' => 'The extension must start with a dot and contain only valid characters.']);
        }
        if (! preg_match('/^[a-z0-9.+-]+\/[a-z0-9.+-]+$/i', $mimeType)) {
            throw ValidationException::withMessages(['mime_type' => 'A valid MIME type is required.']);
        }

        return MimeType::query()->updateOrCreate(
            ['domain_id' => $domain->getKey(), 'extension' => strtolower($extension)],
            ['id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'mime_type' => $mimeType, 'active' => (bool) ($attributes['active'] ?? true)],
        );
    }
}
