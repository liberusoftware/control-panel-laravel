<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Models;

use Illuminate\Database\Eloquent\Model;

final class ContainerLifecycle extends Model
{
    protected $table = 'control_panel_container_lifecycle';

    protected $fillable = ['id', 'team_id', 'workload_id', 'operation', 'status', 'idempotency_key', 'requested_at', 'completed_at', 'details'];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'completed_at' => 'datetime', 'details' => 'array'];
    }
}
