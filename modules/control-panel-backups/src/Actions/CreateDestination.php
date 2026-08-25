<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupDestination;

final class CreateDestination
{
    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): BackupDestination
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $driver = trim((string) ($attributes['driver'] ?? ''));
        if ($name === '' || ! in_array($driver, ['local', 's3', 'sftp', 'ftp'], true)) {
            throw ValidationException::withMessages(['destination' => 'A name and supported storage driver are required.']);
        }

        return BackupDestination::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'name' => $name, 'driver' => $driver, 'config' => $attributes['config'] ?? [], 'retention_days' => max((int) ($attributes['retention_days'] ?? 30), 1), 'default' => (bool) ($attributes['default'] ?? false), 'active' => true]);
    }
}
