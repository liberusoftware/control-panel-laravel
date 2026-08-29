<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;

final class UpdatePolicy
{
    /** @param array<string, mixed> $attributes */
    public function execute(BackupPolicy $policy, array $attributes): BackupPolicy
    {
        $name = trim((string) ($attributes['name'] ?? $policy->name));
        $driver = trim((string) ($attributes['storage_driver'] ?? $policy->storage_driver));
        $retentionDays = (int) ($attributes['retention_days'] ?? $policy->retention_days);
        if ($name === '' || $driver === '' || $retentionDays < 1) {
            throw ValidationException::withMessages(['policy' => 'A policy name, storage driver, and positive retention period are required.']);
        }

        $policy->forceFill([
            'name' => $name,
            'schedule' => $attributes['schedule'] ?? $policy->schedule,
            'retention_days' => $retentionDays,
            'storage_driver' => $driver,
            'storage_config' => $attributes['storage_config'] ?? $policy->storage_config,
            'encrypted' => $attributes['encrypted'] ?? $policy->encrypted,
            'active' => $attributes['active'] ?? $policy->active,
        ])->save();

        return $policy->refresh();
    }
}
