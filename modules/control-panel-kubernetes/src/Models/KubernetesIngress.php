<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Model;

final class KubernetesIngress extends Model
{
    protected $table = 'control_panel_kubernetes_ingress';

    protected $fillable = ['id', 'team_id', 'cluster_id', 'namespace', 'name', 'host', 'paths', 'tls', 'backend', 'status'];

    protected function casts(): array
    {
        return ['paths' => 'array', 'tls' => 'array', 'backend' => 'array'];
    }
}
