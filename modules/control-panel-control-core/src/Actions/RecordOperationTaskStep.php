<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Enums\TaskStepStatus;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;
use Liberu\ControlPanel\ControlCore\Models\OperationTaskStep;

final readonly class RecordOperationTaskStep
{
    /** @param array<string, mixed> $attributes */
    public function execute(OperationTask $task, array $attributes): OperationTaskStep
    {
        $stepKey = trim((string) ($attributes['step_key'] ?? ''));
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($stepKey === '' || $name === '') {
            throw ValidationException::withMessages(['step_key' => 'A step key and name are required.']);
        }

        $status = TaskStepStatus::from((string) ($attributes['status'] ?? TaskStepStatus::Pending->value));
        $step = OperationTaskStep::query()->firstOrNew(['task_id' => $task->getKey(), 'step_key' => $stepKey]);
        if ($task->status === TaskStatus::Cancelled && $status !== TaskStepStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => 'A cancelled task cannot accept an active step.']);
        }
        if ($step->exists && in_array($step->status, [TaskStepStatus::Succeeded, TaskStepStatus::Failed, TaskStepStatus::Cancelled], true) && $step->status !== $status) {
            throw ValidationException::withMessages(['status' => 'A finished task step cannot be reopened.']);
        }
        $step->fill([
            'id' => $step->exists ? $step->getKey() : (string) Str::uuid(),
            'name' => $name,
            'status' => $status,
            'input' => $attributes['input'] ?? $step->input,
            'result' => $attributes['result'] ?? $step->result,
            'error' => $attributes['error'] ?? null,
            'attempts' => $status === TaskStepStatus::Running ? $step->attempts + 1 : $step->attempts,
            'started_at' => $status === TaskStepStatus::Running ? ($step->started_at ?? now()) : $step->started_at,
            'finished_at' => in_array($status, [TaskStepStatus::Succeeded, TaskStepStatus::Failed, TaskStepStatus::Cancelled], true) ? now() : null,
        ])->save();

        return $step->refresh();
    }
}
