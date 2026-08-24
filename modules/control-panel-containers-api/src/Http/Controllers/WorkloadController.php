<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Containers\Actions\RegisterWorkload;
use Liberu\ControlPanel\Containers\Models\Workload;
use Liberu\ControlPanel\Containers\Queries\ListWorkloads;

final class WorkloadController
{
    public function index(Request $request, ListWorkloads $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $items = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $items->through(static fn (Workload $item): array => self::resource($item)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, RegisterWorkload $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:120'], 'image' => ['required', 'string', 'max:255'], 'specification' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    private static function resource(Workload $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-workload', 'attributes' => $item->only(['node_id', 'name', 'image', 'status', 'specification'])];
    }
}
