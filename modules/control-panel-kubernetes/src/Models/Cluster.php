<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Cluster extends Model
{
    use HasUuids;

    protected $table = 'control_panel_kubernetes_clusters';

    protected $fillable = ['team_id', 'name', 'endpoint', 'status', 'configuration'];

    protected $hidden = ['configuration'];

    protected function casts(): array
    {
        return ['configuration' => 'encrypted:array'];
    }
}
