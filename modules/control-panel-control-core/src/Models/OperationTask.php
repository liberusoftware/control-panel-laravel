<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\ControlPanel\ControlCore\Enums\CompensationStatus;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;

final class OperationTask extends Model
{
    use HasUuids;

    protected $table = 'control_panel_operation_tasks';

    protected $fillable = ['team_id', 'node_id', 'operation', 'idempotency_key', 'status', 'payload', 'result', 'error', 'attempts', 'available_at', 'timeout_at', 'finished_at', 'compensation_status', 'compensation_result', 'compensation_error', 'compensation_started_at', 'compensation_finished_at'];

    protected function casts(): array
    {
        return ['status' => TaskStatus::class, 'compensation_status' => CompensationStatus::class, 'payload' => 'array', 'result' => 'array', 'compensation_result' => 'array', 'available_at' => 'datetime', 'timeout_at' => 'datetime', 'finished_at' => 'datetime', 'compensation_started_at' => 'datetime', 'compensation_finished_at' => 'datetime'];
    }

    /** @return HasMany<OperationTaskStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(OperationTaskStep::class, 'task_id');
    }
}
