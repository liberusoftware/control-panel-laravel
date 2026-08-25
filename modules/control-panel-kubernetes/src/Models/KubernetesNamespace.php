<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Model;

final class KubernetesNamespace extends Model
{
    protected $table = 'control_panel_kubernetes_namespaces';

    protected $fillable = ['id', 'team_id', 'cluster_id', 'name', 'status', 'labels', 'quotas'];

    protected function casts(): array
    {
        return ['labels' => 'array', 'quotas' => 'array'];
    }
}
