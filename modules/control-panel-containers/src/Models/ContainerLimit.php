<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Models;

use Illuminate\Database\Eloquent\Model;

final class ContainerLimit extends Model
{
    protected $table = 'control_panel_container_limits';

    protected $fillable = ['id', 'team_id', 'workload_id', 'cpu_millis', 'memory_bytes', 'pids', 'restart_policy'];

    protected function casts(): array
    {
        return ['cpu_millis' => 'integer', 'memory_bytes' => 'integer', 'pids' => 'integer'];
    }
}
