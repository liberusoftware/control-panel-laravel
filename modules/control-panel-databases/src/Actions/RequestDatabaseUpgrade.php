<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Enums\UpgradeStatus;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseUpgrade;

final class RequestDatabaseUpgrade
{
    public function execute(Database $database, string $toVersion): DatabaseUpgrade
    {
        $toVersion = trim($toVersion);
        if ($toVersion === '' || $database->engine?->version === $toVersion) {
            throw ValidationException::withMessages(['to_version' => 'A different target version is required.']);
        }

        return DatabaseUpgrade::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $database->team_id, 'database_id' => $database->getKey(),
            'from_version' => $database->engine?->version, 'to_version' => $toVersion, 'status' => UpgradeStatus::Pending,
            'metadata' => [],
        ]);
    }
}
