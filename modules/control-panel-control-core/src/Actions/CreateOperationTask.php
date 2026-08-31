<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\OperationTaskCreated;
use Liberu\ControlPanel\ControlCore\Exceptions\IdempotencyConflict;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class CreateOperationTask
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): OperationTask
    {
        $operation = trim((string) ($attributes['operation'] ?? ''));
        $key = trim((string) ($attributes['idempotency_key'] ?? ''));
        if ($operation === '' || $key === '') {
            throw ValidationException::withMessages(['operation' => 'An operation and idempotency key are required.']);
        }
        if (($attributes['node_id'] ?? null) !== null && ! Node::query()
            ->whereKey($attributes['node_id'])->where('team_id', $attributes['team_id'] ?? null)->exists()) {
            throw ValidationException::withMessages(['node_id' => 'The node is not available in the current team.']);
        }

        $nodeId = $attributes['node_id'] ?? null;
        $payload = $attributes['payload'] ?? [];
        $timeoutAt = isset($attributes['timeout_seconds'])
            ? now()->addSeconds(max((int) $attributes['timeout_seconds'], 1))
            : ($attributes['timeout_at'] ?? null);

        $task = OperationTask::query()->firstOrCreate(
            ['team_id' => $attributes['team_id'] ?? null, 'idempotency_key' => $key],
            ['id' => (string) Str::uuid(), 'node_id' => $nodeId, 'operation' => $operation, 'status' => TaskStatus::Pending, 'payload' => $payload, 'attempts' => 0, 'timeout_at' => $timeoutAt],
        );

        if (! $task->wasRecentlyCreated && (
            $task->operation !== $operation
            || (string) $task->node_id !== (string) $nodeId
            || ($task->payload ?? []) !== $payload
        )) {
            throw new IdempotencyConflict();
        }

        if ($task->wasRecentlyCreated) {
            $this->events->dispatch(new OperationTaskCreated($task->getKey()));
        }

        return $task;
    }
}
