<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;

final class UpdateDatabase
{
    /** @param array<string, mixed> $attributes */
    public function execute(Database $database, array $attributes): Database
    {
        $name = trim((string) ($attributes['name'] ?? $database->name));
        $engineId = (string) ($attributes['engine_id'] ?? $database->engine_id);

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,127}$/', $name)) {
            throw ValidationException::withMessages(['name' => 'The database name contains unsupported characters.']);
        }

        $engine = DatabaseEngine::query()->whereKey($engineId)->where('active', true)->first();
        if ($engine === null || ($engine->team_id !== null && (string) $engine->team_id !== (string) $database->team_id)) {
            throw ValidationException::withMessages(['engine_id' => 'The database engine is not available in the current team.']);
        }

        $database->forceFill([
            'name' => $name,
            'engine_id' => $engineId,
            'account_id' => $attributes['account_id'] ?? $database->account_id,
            'charset' => $attributes['charset'] ?? $database->charset,
            'collation' => $attributes['collation'] ?? $database->collation,
            'metadata' => $attributes['metadata'] ?? $database->metadata,
        ])->save();

        return $database->refresh();
    }
}
