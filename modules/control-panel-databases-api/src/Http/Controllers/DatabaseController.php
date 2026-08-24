<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Databases\Actions\CreateDatabase;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Queries\ListDatabases;

final class DatabaseController
{
    public function index(Request $request, ListDatabases $list): JsonResponse
    {
        $databases = $list->execute($request->user()?->current_team_id, $request->integer('per_page', 25));

        return response()->json(['data' => $databases->through(static fn (Database $database): array => self::resource($database)), 'meta' => ['current_page' => $databases->currentPage(), 'per_page' => $databases->perPage(), 'total' => $databases->total()]]);
    }

    public function store(Request $request, CreateDatabase $create): JsonResponse
    {
        $data = $request->validate(['engine_id' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:128'], 'account_id' => ['nullable', 'string', 'max:255'], 'metadata' => ['nullable', 'array']]);
        $database = $create->execute(array_merge($data, ['team_id' => $request->user()?->current_team_id]));

        return response()->json(['data' => self::resource($database)], 201);
    }

    private static function resource(Database $database): array
    {
        return ['id' => $database->getKey(), 'type' => 'control-panel-database', 'attributes' => $database->only(['name', 'status', 'engine_id', 'account_id', 'charset', 'collation', 'metadata'])];
    }
}
