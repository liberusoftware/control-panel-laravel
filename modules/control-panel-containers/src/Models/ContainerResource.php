<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Containers\Models;

use Illuminate\Database\Eloquent\Model;

final class ContainerResource extends Model
{
    protected $table = 'control_panel_container_resources';

    protected $fillable = ['id', 'team_id', 'workload_id', 'kind', 'name', 'status', 'spec'];

    protected function casts(): array
    {
        return ['spec' => 'array'];
    }
}
