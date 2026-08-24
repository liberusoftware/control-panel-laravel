<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Support\Str;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseHealthCheck;

final class RecordDatabaseHealth
{
    /** @param array<string, mixed> $details */
    public function execute(Database $database, bool $healthy, ?int $latencyMs = null, ?string $message = null, array $details = []): DatabaseHealthCheck
    {
        return DatabaseHealthCheck::query()->create([
            'id' => (string) Str::uuid(), 'team_id' => $database->team_id, 'database_id' => $database->getKey(),
            'healthy' => $healthy, 'latency_ms' => $latencyMs, 'message' => $message, 'details' => $details, 'checked_at' => now(),
        ]);
    }
}
