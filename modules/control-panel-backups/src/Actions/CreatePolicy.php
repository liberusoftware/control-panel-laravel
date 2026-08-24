<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;

final class CreatePolicy
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): BackupPolicy
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $driver = trim((string) ($attributes['storage_driver'] ?? ''));
        if ($name === '' || $driver === '') {
            throw ValidationException::withMessages(['policy' => 'A policy name and storage driver are required.']);
        }

        return DB::transaction(fn (): BackupPolicy => BackupPolicy::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'name' => $name, 'schedule' => $attributes['schedule'] ?? [], 'retention_days' => max((int) ($attributes['retention_days'] ?? 30), 1), 'storage_driver' => $driver, 'storage_config' => $attributes['storage_config'] ?? [], 'encrypted' => (bool) ($attributes['encrypted'] ?? true), 'active' => true]));
    }
}
