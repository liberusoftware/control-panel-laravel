<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\ControlPanel\ControlCore\Enums\TaskStepStatus;

final class OperationTaskStep extends Model
{
    use HasUuids;

    protected $table = 'control_panel_operation_task_steps';

    protected $fillable = ['task_id', 'step_key', 'name', 'status', 'input', 'result', 'error', 'attempts', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['status' => TaskStepStatus::class, 'input' => 'array', 'result' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    /** @return BelongsTo<OperationTask, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(OperationTask::class, 'task_id');
    }
}
