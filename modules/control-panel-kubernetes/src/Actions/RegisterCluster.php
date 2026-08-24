<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Liberu\ControlPanel\Kubernetes\Models\Cluster;

final class RegisterCluster
{
    public function execute(array $attributes): Cluster
    {
        return Cluster::query()->create(array_merge(['status' => 'pending', 'configuration' => []], $attributes));
    }
}
