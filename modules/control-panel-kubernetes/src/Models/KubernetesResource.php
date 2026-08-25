<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Models;

use Illuminate\Database\Eloquent\Model;

final class KubernetesResource extends Model
{
    protected $table = 'control_panel_kubernetes_resources';

    protected $fillable = ['id', 'team_id', 'cluster_id', 'kind', 'name', 'namespace', 'status', 'spec'];

    protected function casts(): array
    {
        return ['spec' => 'array'];
    }
}
