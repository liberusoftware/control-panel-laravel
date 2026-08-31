<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($attributes, $name, $driver): BackupDestination {
            $teamId = $attributes['team_id'] ?? null;
            $isDefault = (bool) ($attributes['default'] ?? false);

            if ($isDefault) {
                BackupDestination::query()->where('team_id', $teamId)->where('default', true)->update(['default' => false]);
            }

            return BackupDestination::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'name' => $name, 'driver' => $driver, 'config' => $attributes['config'] ?? [], 'retention_days' => max((int) ($attributes['retention_days'] ?? 30), 1), 'default' => $isDefault, 'active' => true]);
        });
    }
}
