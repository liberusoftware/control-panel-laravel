<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Model;

final class KubernetesStorageClaim extends Model
{
    protected $table = 'control_panel_kubernetes_storage';

    protected $fillable = ['id', 'team_id', 'cluster_id', 'namespace', 'name', 'storage_class', 'capacity_bytes', 'access_modes', 'status'];

    protected function casts(): array
    {
        return ['capacity_bytes' => 'integer', 'access_modes' => 'array'];
    }
}
