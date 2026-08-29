<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesApi\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Liberu\ControlPanel\Databases\Actions\ArchiveDatabase;
use Liberu\ControlPanel\Databases\Actions\ConfigureRemoteAccess;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseBackup;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseUser;
use Liberu\ControlPanel\Databases\Actions\DeleteDatabase;
use Liberu\ControlPanel\Databases\Actions\GrantDatabasePrivilege;
use Liberu\ControlPanel\Databases\Actions\RecordDatabaseHealth;
use Liberu\ControlPanel\Databases\Actions\RequestDatabaseUpgrade;
use Liberu\ControlPanel\Databases\Actions\SuspendDatabase;
use Liberu\ControlPanel\Databases\Actions\UpdateDatabase;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Models\DatabaseUser;
use Liberu\ControlPanel\Databases\Queries\ListDatabases;

final class DatabaseController
{
    public function index(Request $request, ListDatabases $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $databases = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $databases->through(static fn (Database $database): array => self::resource($database)), 'meta' => ['current_page' => $databases->currentPage(), 'per_page' => $databases->perPage(), 'total' => $databases->total()]]);
    }

    public function user(Request $request, Database $database, CreateDatabaseUser $create): JsonResponse
    {
        $this->assertTeam($request, $database);
        $data = $request->validate(['username' => ['required', 'string', 'max:128'], 'host' => ['nullable', 'string', 'max:255'], 'password' => ['required', 'string', 'min:16', 'max:512']]);
        $user = $create->execute($database, $data);

        return response()->json(['data' => ['id' => $user->getKey(), 'type' => 'control-panel-database-user', 'attributes' => $user->only(['database_id', 'username', 'host', 'active'])]], 201);
    }

    public function privilege(Request $request, DatabaseUser $user, GrantDatabasePrivilege $grant): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        abort_unless((string) $user->team_id === (string) $teamId, 404);
        $data = $request->validate(['privilege' => ['required', 'string', 'max:40'], 'object_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.*:-]+$/']]);
        $privilege = $grant->execute($user, $data['privilege'], $data['object_name']);

        return response()->json(['data' => ['id' => $privilege->getKey(), 'type' => 'control-panel-database-privilege', 'attributes' => $privilege->only(['database_id', 'database_user_id', 'privilege', 'object_name'])]], 201);
    }

    public function backup(Request $request, Database $database, CreateDatabaseBackup $create): JsonResponse
    {
        $this->assertTeam($request, $database);
        $data = $request->validate(['destination' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:database,logical,snapshot'], 'automated' => ['sometimes', 'boolean']]);
        $backup = $create->execute($database, $data);

        return response()->json(['data' => ['id' => $backup->getKey(), 'type' => 'control-panel-database-backup', 'attributes' => $backup->only(['database_id', 'destination', 'type', 'status', 'automated'])]], 201);
    }

    public function health(Request $request, Database $database, RecordDatabaseHealth $record): JsonResponse
    {
        $this->assertTeam($request, $database);
        $data = $request->validate(['healthy' => ['required', 'boolean'], 'latency_ms' => ['nullable', 'integer', 'min:0'], 'message' => ['nullable', 'string', 'max:255'], 'details' => ['nullable', 'array']]);
        $health = $record->execute($database, $data['healthy'], $data['latency_ms'] ?? null, $data['message'] ?? null, $data['details'] ?? []);

        return response()->json(['data' => ['id' => $health->getKey(), 'type' => 'control-panel-database-health-check', 'attributes' => $health->only(['database_id', 'healthy', 'latency_ms', 'message', 'details', 'checked_at'])]], 201);
    }

    public function upgrade(Request $request, Database $database, RequestDatabaseUpgrade $requestUpgrade): JsonResponse
    {
        $this->assertTeam($request, $database);
        $data = $request->validate(['to_version' => ['required', 'string', 'max:80']]);
        $upgrade = $requestUpgrade->execute($database, $data['to_version']);

        return response()->json(['data' => ['id' => $upgrade->getKey(), 'type' => 'control-panel-database-upgrade', 'attributes' => $upgrade->only(['database_id', 'from_version', 'to_version', 'status'])]], 202);
    }

    public function remoteAccess(Request $request, Database $database, ConfigureRemoteAccess $configure): JsonResponse
    {
        $this->assertTeam($request, $database);
        $data = $request->validate(['source_cidr' => ['required', 'string', 'max:64'], 'port' => ['required', 'integer', 'between:1,65535'], 'tls_required' => ['sometimes', 'boolean'], 'expires_at' => ['nullable', 'date']]);
        $access = $configure->execute($database, $data);

        return response()->json(['data' => ['id' => $access->getKey(), 'type' => 'control-panel-database-remote-access', 'attributes' => $access->only(['database_id', 'source_cidr', 'port', 'tls_required', 'active', 'expires_at'])]], 201);
    }

    public function suspend(Request $request, Database $database, SuspendDatabase $suspend): JsonResponse
    {
        $this->assertTeam($request, $database);

        return response()->json(['data' => self::resource($suspend->execute($database))]);
    }

    public function archive(Request $request, Database $database, ArchiveDatabase $archive): JsonResponse
    {
        $this->assertTeam($request, $database);

        return response()->json(['data' => self::resource($archive->execute($database))]);
    }

    public function delete(Request $request, Database $database, DeleteDatabase $delete): JsonResponse
    {
        $this->assertTeam($request, $database);
        $delete->execute($database);

        return response()->json(status: 204);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Database::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-database', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, CreateDatabase $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['engine_id' => ['required', 'uuid', Rule::exists('control_panel_database_engines', 'id')->where(function (Builder $query) use ($teamId): void {
            $query->where('team_id', $teamId)->orWhereNull('team_id');
        })], 'name' => ['required', 'string', 'max:128'], 'account_id' => ['nullable', 'string', 'max:255'], 'metadata' => ['nullable', 'array']]);
        $database = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($database)], 201);
    }

    public function update(Request $request, string $id, UpdateDatabase $update): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $database = Database::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:128'],
            'engine_id' => ['sometimes', 'uuid', Rule::exists('control_panel_database_engines', 'id')->where(function (Builder $query) use ($teamId): void {
                $query->where('active', true)->where(function (Builder $query) use ($teamId): void {
                    $query->whereNull('team_id')->orWhere('team_id', $teamId);
                });
            })],
            'account_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'charset' => ['sometimes', 'string', 'max:40'],
            'collation' => ['sometimes', 'string', 'max:80'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json(['data' => self::resource($update->execute($database, $data))]);
    }

    private static function resource(Database $database): array
    {
        return ['id' => $database->getKey(), 'type' => 'control-panel-database', 'attributes' => $database->only(['name', 'status', 'engine_id', 'account_id', 'charset', 'collation', 'metadata'])];
    }

    private function assertTeam(Request $request, Database $database): void
    {
        abort_if($request->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $database->team_id === (string) $request->user()?->current_team_id, 404);
    }
}
