<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Containers\Actions\RecordContainerResource;
use Liberu\ControlPanel\Containers\Actions\RegisterContainerAsset;
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

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Workload::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-workload', 'attributes' => $item->toArray()]]);
    }

    public function store(Request $request, RegisterWorkload $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:120'], 'image' => ['required', 'string', 'max:255'], 'specification' => ['nullable', 'array']]);
        $item = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($item)], 201);
    }

    public function resourceRecord(Request $request, RecordContainerResource $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['workload_id' => ['nullable', 'uuid'], 'kind' => ['required', 'in:image,registry,network,volume,secret,limit,lifecycle'], 'name' => ['required', 'string', 'max:255'], 'status' => ['nullable', 'string', 'max:50'], 'spec' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-container-resource', 'attributes' => $item->only(['workload_id', 'kind', 'name', 'status', 'spec'])]], 201);
    }

    public function asset(Request $request, RegisterContainerAsset $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:image,registry,network,volume,secret,limit,lifecycle'], 'payload' => ['required', 'array']]);
        $item = $register->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-container-'.$data['kind'], 'attributes' => $item->toArray()]], 201);
    }

    private static function resource(Workload $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-workload', 'attributes' => $item->only(['node_id', 'name', 'image', 'status', 'specification'])];
    }
}
