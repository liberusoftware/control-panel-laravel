<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\OperationTaskTransitioned;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class TransitionOperationTask
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed>|null $result */
    public function execute(OperationTask $task, TaskStatus $status, ?array $result = null, ?string $error = null): OperationTask
    {
        if (in_array($task->status, [TaskStatus::Succeeded, TaskStatus::Failed, TaskStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['status' => 'A finished task cannot be transitioned.']);
        }
        if ($status === TaskStatus::Running && $task->status !== TaskStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'Only pending tasks can start.']);
        }

        return DB::transaction(function () use ($task, $status, $result, $error): OperationTask {
            $task->forceFill([
                'status' => $status,
                'result' => $result ?? $task->result,
                'error' => $error,
                'attempts' => $status === TaskStatus::Running ? $task->attempts + 1 : $task->attempts,
                'finished_at' => in_array($status, [TaskStatus::Succeeded, TaskStatus::Failed, TaskStatus::Cancelled], true) ? now() : null,
            ])->save();
            $this->events->dispatch(new OperationTaskTransitioned($task->getKey(), $status->value));

            return $task->refresh();
        });
    }
}
