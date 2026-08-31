<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\CompensationStatus;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Events\OperationTaskCompensationTransitioned;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final readonly class RecordOperationTaskCompensation
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed>|null $result */
    public function execute(OperationTask $task, CompensationStatus $status, ?array $result = null, ?string $error = null): OperationTask
    {
        return DB::transaction(function () use ($task, $status, $result, $error): OperationTask {
            $lockedTask = OperationTask::query()->lockForUpdate()->findOrFail($task->getKey());
            if (! in_array($lockedTask->status, [TaskStatus::Succeeded, TaskStatus::Failed, TaskStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['status' => 'Compensation requires a terminal operation task.']);
            }
            if (in_array($lockedTask->compensation_status, [CompensationStatus::Succeeded, CompensationStatus::Failed], true) && $lockedTask->compensation_status !== $status) {
                throw ValidationException::withMessages(['compensation_status' => 'A finished compensation cannot be changed.']);
            }
            if ($status === CompensationStatus::NotRequired) {
                throw ValidationException::withMessages(['compensation_status' => 'Use an explicit compensation outcome.']);
            }

            $lockedTask->forceFill([
                'compensation_status' => $status,
                'compensation_result' => $result ?? $lockedTask->compensation_result,
                'compensation_error' => $error,
                'compensation_started_at' => $status === CompensationStatus::Running ? ($lockedTask->compensation_started_at ?? now()) : $lockedTask->compensation_started_at,
                'compensation_finished_at' => in_array($status, [CompensationStatus::Succeeded, CompensationStatus::Failed], true) ? now() : null,
            ])->save();
            $this->events->dispatch(new OperationTaskCompensationTransitioned($lockedTask->getKey(), $status->value));

            return $lockedTask->refresh();
        });
    }
}
