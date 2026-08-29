<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;

final class UpdateHostedApplication
{
    /** @param array<string, mixed> $attributes */
    public function execute(HostedApplication $application, array $attributes): HostedApplication
    {
        $name = trim((string) ($attributes['name'] ?? $application->name));
        $type = (string) ($attributes['type'] ?? $application->type);
        $documentRoot = trim((string) ($attributes['document_root'] ?? $application->document_root));
        $domainId = $attributes['domain_id'] ?? $application->domain_id;

        if ($name === '' || ! in_array($type, ['wordpress', 'laravel', 'static', 'nodejs', 'custom'], true)) {
            throw ValidationException::withMessages(['application' => 'A valid application name and type are required.']);
        }

        if (! str_starts_with($documentRoot, '/') || mb_strlen($documentRoot) > 2048) {
            throw ValidationException::withMessages(['document_root' => 'The document root must be an absolute path.']);
        }

        if (! Domain::query()->whereKey($domainId)->where('team_id', $application->team_id)->exists()) {
            throw ValidationException::withMessages(['domain_id' => 'The domain must belong to the current team.']);
        }

        $application->forceFill([
            'domain_id' => $domainId,
            'name' => $name,
            'type' => $type,
            'version' => $attributes['version'] ?? $application->version,
            'document_root' => $documentRoot,
            'config' => $attributes['config'] ?? $application->config,
        ])->save();

        return $application->refresh();
    }
}
