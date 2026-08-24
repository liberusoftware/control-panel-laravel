<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Queries\ListOperationTasks;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final class OperationTaskController
{
    public function index(Request $request, ListOperationTasks $tasks): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = $tasks->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $page->through(static fn ($task): array => [
            'id' => $task->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $task->only(['node_id', 'operation', 'idempotency_key', 'status', 'payload', 'result', 'error', 'attempts', 'available_at', 'finished_at']),
        ]), 'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function store(Request $request, CreateOperationTask $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['node_id' => ['nullable', 'uuid'], 'operation' => ['required', 'string', 'max:120'], 'idempotency_key' => ['required', 'string', 'max:160'], 'payload' => ['nullable', 'array']]);
        $task = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $task->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $task->only(['node_id', 'operation', 'idempotency_key', 'status', 'payload', 'attempts'])]], 201);
    }

    public function transition(Request $request, string $task, TransitionOperationTask $transition): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['status' => ['required', 'string', 'in:running,succeeded,failed,cancelled'], 'result' => ['nullable', 'array'], 'error' => ['nullable', 'string', 'max:10000']]);
        $updated = $transition->execute($item, TaskStatus::from($data['status']), $data['result'] ?? null, $data['error'] ?? null);

        return response()->json(['data' => ['id' => $updated->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $updated->only(['node_id', 'operation', 'status', 'result', 'error', 'attempts', 'finished_at'])]]);
    }
}
