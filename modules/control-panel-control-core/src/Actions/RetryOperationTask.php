<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\OperationTaskTransitioned;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class RetryOperationTask
{
    public function __construct(private Dispatcher $events) {}

    public function execute(OperationTask $task): OperationTask
    {
        return DB::transaction(function () use ($task): OperationTask {
            $lockedTask = OperationTask::query()->lockForUpdate()->findOrFail($task->getKey());

            if ($lockedTask->status !== TaskStatus::Failed) {
                throw ValidationException::withMessages(['status' => 'Only failed tasks can be retried.']);
            }

            $lockedTask->forceFill([
                'status' => TaskStatus::Pending,
                'result' => null,
                'error' => null,
                'available_at' => now(),
                'finished_at' => null,
            ])->save();
            $this->events->dispatch(new OperationTaskTransitioned($lockedTask->getKey(), TaskStatus::Pending->value));

            return $lockedTask->refresh();
        });
    }
}
