<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Enums\BackupStatus;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseBackup;

final class CreateDatabaseBackup
{
    /** @param array<string, mixed> $attributes */
    public function execute(Database $database, array $attributes): DatabaseBackup
    {
        $destination = trim((string) ($attributes['destination'] ?? ''));
        $type = trim((string) ($attributes['type'] ?? 'database'));
        if ($destination === '' || ! in_array($type, ['database', 'logical', 'snapshot'], true)) {
            throw ValidationException::withMessages(['backup' => 'A supported backup type and destination are required.']);
        }

        return DatabaseBackup::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $database->team_id, 'database_id' => $database->getKey(),
            'destination' => $destination, 'type' => $type, 'status' => BackupStatus::Pending,
            'automated' => (bool) ($attributes['automated'] ?? false),
        ]);
    }
}
