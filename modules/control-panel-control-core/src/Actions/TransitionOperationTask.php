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
        return DB::transaction(function () use ($task, $status, $result, $error): OperationTask {
            $lockedTask = OperationTask::query()->lockForUpdate()->findOrFail($task->getKey());

            if (in_array($lockedTask->status, [TaskStatus::Succeeded, TaskStatus::Failed, TaskStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['status' => 'A finished task cannot be transitioned.']);
            }
            if ($status === TaskStatus::Running && $lockedTask->status !== TaskStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Only pending tasks can start.']);
            }

            $lockedTask->forceFill([
                'status' => $status,
                'result' => $result ?? $lockedTask->result,
                'error' => $error,
                'attempts' => $status === TaskStatus::Running ? $lockedTask->attempts + 1 : $lockedTask->attempts,
                'finished_at' => in_array($status, [TaskStatus::Succeeded, TaskStatus::Failed, TaskStatus::Cancelled], true) ? now() : null,
            ])->save();
            $this->events->dispatch(new OperationTaskTransitioned($lockedTask->getKey(), $status->value));

            return $lockedTask->refresh();
        });
    }
}
