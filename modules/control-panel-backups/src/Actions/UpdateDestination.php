<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupDestination;

final class UpdateDestination
{
    /** @param array<string, mixed> $attributes */
    public function execute(BackupDestination $destination, array $attributes): BackupDestination
    {
        $name = trim((string) ($attributes['name'] ?? $destination->name));
        $driver = trim((string) ($attributes['driver'] ?? $destination->driver));
        $retentionDays = (int) ($attributes['retention_days'] ?? $destination->retention_days);
        if ($name === '' || ! in_array($driver, ['local', 's3', 'sftp', 'ftp'], true) || $retentionDays < 1) {
            throw ValidationException::withMessages(['destination' => 'A name, supported storage driver, and positive retention period are required.']);
        }

        return DB::transaction(function () use ($destination, $attributes, $name, $driver, $retentionDays): BackupDestination {
            $isDefault = (bool) ($attributes['default'] ?? $destination->default);

            if ($isDefault) {
                BackupDestination::query()->where('team_id', $destination->team_id)->whereKeyNot($destination->getKey())->where('default', true)->update(['default' => false]);
            }

            $destination->forceFill(['name' => $name, 'driver' => $driver, 'config' => $attributes['config'] ?? $destination->config, 'retention_days' => $retentionDays, 'default' => $isDefault, 'active' => $attributes['active'] ?? $destination->active])->save();

            return $destination->refresh();
        });
    }
}
