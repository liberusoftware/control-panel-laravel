<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\OperationTaskCreated;
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

        $task = OperationTask::query()->firstOrCreate(
            ['team_id' => $attributes['team_id'] ?? null, 'idempotency_key' => $key],
            ['id' => (string) Str::uuid(), 'node_id' => $attributes['node_id'] ?? null, 'operation' => $operation, 'status' => TaskStatus::Pending, 'payload' => $attributes['payload'] ?? [], 'attempts' => 0],
        );
        if ($task->wasRecentlyCreated) {
            $this->events->dispatch(new OperationTaskCreated($task->getKey()));
        }

        return $task;
    }
}
