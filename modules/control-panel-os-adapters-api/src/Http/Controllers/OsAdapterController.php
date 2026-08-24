<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\OsAdapters\Actions\RegisterOsAdapter;
use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;
use Liberu\ControlPanel\OsAdapters\Queries\ListOsAdapters;

final class OsAdapterController
{
    public function index(Request $request, ListOsAdapters $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (OsAdapter $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, RegisterOsAdapter $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'operating_system' => ['required', 'string', 'max:80'], 'version' => ['required', 'string', 'max:80'], 'capabilities' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(OsAdapter $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-os-adapter', 'attributes' => $item->only(['node_id', 'operating_system', 'version', 'capabilities', 'status', 'metadata'])];
    }
}
