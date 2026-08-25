<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ControlCore\Actions\AcquireOperationLock;
use Liberu\ControlPanel\ControlCore\Actions\ReleaseOperationLock;
use Liberu\ControlPanel\ControlCore\Models\OperationLock;

final class OperationLockController
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = OperationLock::query()->where('team_id', $teamId)->latest()->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json(['data' => $page->through(fn (OperationLock $lock): array => $this->resource($lock)), 'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function store(Request $request, AcquireOperationLock $acquire): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'operation_key' => ['required', 'string', 'max:120'], 'owner' => ['required', 'string', 'max:160'], 'ttl_seconds' => ['nullable', 'integer', 'min:1', 'max:86400']]);
        $lock = $acquire->execute($teamId, $data['node_id'], $data['operation_key'], $data['owner'], $data['ttl_seconds'] ?? 300);

        return response()->json(['data' => $this->resource($lock)], 201);
    }

    public function destroy(Request $request, string $lock, ReleaseOperationLock $release): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationLock::query()->whereKey($lock)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['owner' => ['required', 'string', 'max:160']]);
        $release->execute($item, $data['owner']);

        return response()->json(status: 204);
    }

    /** @return array<string, mixed> */
    private function resource(OperationLock $lock): array
    {
        return ['id' => $lock->getKey(), 'type' => 'control-panel-operation-lock', 'attributes' => $lock->only(['node_id', 'operation_key', 'owner', 'expires_at'])];
    }
}
