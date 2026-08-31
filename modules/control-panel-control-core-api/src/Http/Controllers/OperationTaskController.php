<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\ControlCore\Actions\CancelOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\CreateOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\RecordOperationTaskCompensation;
use Liberu\ControlPanel\ControlCore\Actions\RecordOperationTaskStep;
use Liberu\ControlPanel\ControlCore\Actions\RetryOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\TimeoutOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Enums\CompensationStatus;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Exceptions\IdempotencyConflict;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;
use Liberu\ControlPanel\ControlCore\Models\OperationTaskStep;
use Liberu\ControlPanel\ControlCore\Queries\ListOperationTasks;

final class OperationTaskController
{
    public function index(Request $request, ListOperationTasks $tasks): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = $tasks->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $page->through(static fn ($task): array => [
            'id' => $task->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $task->only(['node_id', 'operation', 'idempotency_key', 'status', 'payload', 'result', 'error', 'attempts', 'available_at', 'timeout_at', 'finished_at', 'compensation_status', 'compensation_result', 'compensation_error', 'compensation_started_at', 'compensation_finished_at']),
        ]), 'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function store(Request $request, CreateOperationTask $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $request->merge(['idempotency_key' => $request->input('idempotency_key', $request->header('Idempotency-Key'))]);
        $data = $request->validate(['node_id' => ['nullable', 'uuid'], 'operation' => ['required', 'string', 'max:120'], 'idempotency_key' => ['required', 'string', 'max:160'], 'payload' => ['nullable', 'array'], 'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:604800']]);
        try {
            $task = $create->execute(array_merge($data, ['team_id' => $teamId]));
        } catch (IdempotencyConflict $exception) {
            return response()->json(['title' => 'Idempotency conflict', 'detail' => $exception->getMessage(), 'status' => 409], 409);
        }

        return response()->json(['data' => ['id' => $task->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $task->only(['node_id', 'operation', 'idempotency_key', 'status', 'payload', 'attempts', 'timeout_at'])]], 201);
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

    public function retry(Request $request, string $task, RetryOperationTask $retry): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();
        $updated = $retry->execute($item);

        return response()->json(['data' => ['id' => $updated->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $updated->only(['node_id', 'operation', 'status', 'attempts', 'available_at', 'finished_at'])]]);
    }

    public function cancel(Request $request, string $task, CancelOperationTask $cancel): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();
        $updated = $cancel->execute($item);

        return response()->json(['data' => ['id' => $updated->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $updated->only(['node_id', 'operation', 'status', 'error', 'attempts', 'finished_at'])]]);
    }

    public function timeout(Request $request, string $task, TimeoutOperationTask $timeout): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();
        $updated = $timeout->execute($item);

        return response()->json(['data' => ['id' => $updated->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $updated->only(['node_id', 'operation', 'status', 'error', 'attempts', 'timeout_at', 'finished_at'])]]);
    }

    public function compensation(Request $request, string $task, RecordOperationTaskCompensation $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['status' => ['required', 'string', 'in:pending,running,succeeded,failed'], 'result' => ['nullable', 'array'], 'error' => ['nullable', 'string', 'max:10000']]);
        $updated = $record->execute($item, CompensationStatus::from($data['status']), $data['result'] ?? null, $data['error'] ?? null);

        return response()->json(['data' => ['id' => $updated->getKey(), 'type' => 'control-panel-operation-task', 'attributes' => $updated->only(['node_id', 'operation', 'status', 'compensation_status', 'compensation_result', 'compensation_error', 'compensation_started_at', 'compensation_finished_at'])]]);
    }

    public function steps(Request $request, string $task): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => $item->steps()->oldest()->get()->map(fn (OperationTaskStep $step): array => $this->stepResource($step))->all()]);
    }

    public function recordStep(Request $request, string $task, RecordOperationTaskStep $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = OperationTask::query()->whereKey($task)->where('team_id', $teamId)->firstOrFail();
        $data = $request->validate(['step_key' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:160'], 'status' => ['required', 'string', 'in:pending,running,succeeded,failed,cancelled'], 'input' => ['nullable', 'array'], 'result' => ['nullable', 'array'], 'error' => ['nullable', 'string', 'max:10000']]);
        $step = $record->execute($item, $data);

        return response()->json(['data' => $this->stepResource($step)], $step->wasRecentlyCreated ? 201 : 200);
    }

    /** @return array<string, mixed> */
    private function stepResource(OperationTaskStep $step): array
    {
        return ['id' => $step->getKey(), 'type' => 'control-panel-operation-task-step', 'attributes' => $step->only(['task_id', 'step_key', 'name', 'status', 'input', 'result', 'error', 'attempts', 'started_at', 'finished_at'])];
    }
}
