<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;

final class OperationTask extends Model
{
    use HasUuids;

    protected $table = 'control_panel_operation_tasks';

    protected $fillable = ['team_id', 'node_id', 'operation', 'idempotency_key', 'status', 'payload', 'result', 'error', 'attempts', 'available_at', 'finished_at'];

    protected function casts(): array
    {
        return ['status' => TaskStatus::class, 'payload' => 'array', 'result' => 'array', 'available_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
