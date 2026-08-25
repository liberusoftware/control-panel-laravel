<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Model;

final class KubernetesAutoscaler extends Model
{
    protected $table = 'control_panel_kubernetes_autoscalers';

    protected $fillable = ['id', 'team_id', 'cluster_id', 'namespace', 'name', 'target', 'min_replicas', 'max_replicas', 'metric', 'status'];

    protected function casts(): array
    {
        return ['min_replicas' => 'integer', 'max_replicas' => 'integer'];
    }
}
