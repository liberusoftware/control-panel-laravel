<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesApi\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\Actions\CreateDatabaseUser;
use Liberu\ControlPanel\Databases\Actions\GrantDatabasePrivilege;
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
        abort_unless((string) $database->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['username' => ['required', 'string', 'max:128'], 'host' => ['nullable', 'string', 'max:255'], 'password' => ['required', 'string', 'min:16', 'max:512']]);
        $user = $create->execute($database, $data);

        return response()->json(['data' => ['id' => $user->getKey(), 'type' => 'control-panel-database-user', 'attributes' => $user->only(['database_id', 'username', 'host', 'active'])]], 201);
    }

    public function privilege(Request $request, DatabaseUser $user, GrantDatabasePrivilege $grant): JsonResponse
    {
        abort_unless((string) $user->team_id === (string) $request->user()?->current_team_id, 404);
        $data = $request->validate(['privilege' => ['required', 'string', 'max:40'], 'object_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.*:-]+$/']]);
        $privilege = $grant->execute($user, $data['privilege'], $data['object_name']);

        return response()->json(['data' => ['id' => $privilege->getKey(), 'type' => 'control-panel-database-privilege', 'attributes' => $privilege->only(['database_id', 'database_user_id', 'privilege', 'object_name'])]], 201);
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

    private static function resource(Database $database): array
    {
        return ['id' => $database->getKey(), 'type' => 'control-panel-database', 'attributes' => $database->only(['name', 'status', 'engine_id', 'account_id', 'charset', 'collation', 'metadata'])];
    }
}
