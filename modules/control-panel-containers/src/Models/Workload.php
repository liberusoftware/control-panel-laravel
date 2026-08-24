<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Workload extends Model
{
    use HasUuids;

    protected $table = 'control_panel_container_workloads';

    protected $fillable = ['team_id', 'node_id', 'name', 'image', 'status', 'specification'];

    protected function casts(): array
    {
        return ['specification' => 'array'];
    }
}
