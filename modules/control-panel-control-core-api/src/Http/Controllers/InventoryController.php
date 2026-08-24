<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ControlCore\Actions\RecordInventory;
use Liberu\ControlPanel\ControlCore\Queries\ListInventory;

final class InventoryController
{
    public function index(Request $request, ListInventory $inventory): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = $inventory->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $page->through(static fn ($record): array => [
            'id' => $record->getKey(), 'type' => 'control-panel-inventory-record', 'attributes' => $record->only(['node_id', 'kind', 'record_key', 'value', 'observed_at']),
        ]), 'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function store(Request $request, RecordInventory $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'kind' => ['required', 'string', 'max:80'], 'record_key' => ['required', 'string', 'max:160'], 'value' => ['nullable', 'array'], 'observed_at' => ['nullable', 'date']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-inventory-record', 'attributes' => $item->only(['node_id', 'kind', 'record_key', 'value', 'observed_at'])]], 201);
    }
}
