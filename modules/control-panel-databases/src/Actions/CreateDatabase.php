<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Databases\Enums\DatabaseStatus;
use Liberu\ControlPanel\Databases\Events\DatabaseCreated;
use Liberu\ControlPanel\Databases\Models\Database;

final readonly class CreateDatabase
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Database
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $engineId = trim((string) ($attributes['engine_id'] ?? ''));
        if ($name === '' || $engineId === '') {
            throw ValidationException::withMessages(['database' => 'A database name and engine are required.']);
        }

        return DB::transaction(function () use ($attributes, $name, $engineId): Database {
            $database = Database::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'engine_id' => $engineId, 'account_id' => $attributes['account_id'] ?? null, 'name' => $name, 'status' => DatabaseStatus::Provisioning, 'charset' => $attributes['charset'] ?? 'utf8mb4', 'collation' => $attributes['collation'] ?? 'utf8mb4_unicode_ci', 'metadata' => $attributes['metadata'] ?? []]);
            $this->events->dispatch(new DatabaseCreated($database));

            return $database;
        });
    }
}
