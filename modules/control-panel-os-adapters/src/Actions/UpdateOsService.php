<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\OsAdapters\Models\OsService;

final class UpdateOsService
{
    /** @param array<string, mixed> $attributes */
    public function execute(OsService $service, array $attributes): OsService
    {
        $name = trim((string) ($attributes['name'] ?? $service->name));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A service name is required.']);
        }

        $service->forceFill([
            'name' => $name,
            'version' => $attributes['version'] ?? $service->version,
            'status' => $attributes['status'] ?? $service->status,
            'enabled' => $attributes['enabled'] ?? $service->enabled,
            'metadata' => $attributes['metadata'] ?? $service->metadata,
        ])->save();

        return $service->refresh();
    }
}
