<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesResource;

final class RecordKubernetesResource
{
    public function execute(array $a): KubernetesResource
    {
        $teamId = trim((string) ($a['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        $kind = (string) ($a['kind'] ?? '');
        if (! in_array($kind, ['node', 'namespace', 'rbac', 'workload', 'ingress', 'helm', 'storage', 'autoscaling', 'upgrade', 'cluster-view'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported Kubernetes resource.']);
        }

        $clusterId = $a['cluster_id'] ?? null;
        if ($clusterId !== null && ! Cluster::query()->whereKey($clusterId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return KubernetesResource::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'cluster_id' => $clusterId, 'kind' => $kind, 'name' => trim((string) ($a['name'] ?? '')), 'namespace' => $a['namespace'] ?? null, 'status' => $a['status'] ?? 'active', 'spec' => $a['spec'] ?? []]);
    }
}
