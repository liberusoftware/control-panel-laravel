<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\OperationTaskTransitioned;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class TimeoutOperationTask
{
    public function __construct(private Dispatcher $events) {}

    public function execute(OperationTask $task): OperationTask
    {
        return DB::transaction(function () use ($task): OperationTask {
            $lockedTask = OperationTask::query()->lockForUpdate()->findOrFail($task->getKey());

            if ($lockedTask->status !== TaskStatus::Running) {
                throw ValidationException::withMessages(['status' => 'Only running tasks can time out.']);
            }
            if ($lockedTask->timeout_at === null || Carbon::now()->lt($lockedTask->timeout_at)) {
                throw ValidationException::withMessages(['timeout_at' => 'The task timeout has not elapsed.']);
            }

            $lockedTask->forceFill([
                'status' => TaskStatus::Failed,
                'error' => 'Task timed out.',
                'finished_at' => now(),
            ])->save();
            $this->events->dispatch(new OperationTaskTransitioned($lockedTask->getKey(), TaskStatus::Failed->value));

            return $lockedTask->refresh();
        });
    }
}
