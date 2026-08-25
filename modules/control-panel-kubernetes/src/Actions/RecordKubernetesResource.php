<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesResource;

final class RecordKubernetesResource
{
    public function execute(array $a): KubernetesResource
    {
        $kind = (string) ($a['kind'] ?? '');
        if (! in_array($kind, ['node', 'namespace', 'rbac', 'workload', 'ingress', 'helm', 'storage', 'autoscaling', 'upgrade', 'cluster-view'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported Kubernetes resource.']);
        }

return KubernetesResource::query()->create(['id' => (string) Str::uuid(), 'team_id' => $a['team_id'] ?? null, 'cluster_id' => $a['cluster_id'] ?? null, 'kind' => $kind, 'name' => trim((string) ($a['name'] ?? '')), 'namespace' => $a['namespace'] ?? null, 'status' => $a['status'] ?? 'active', 'spec' => $a['spec'] ?? []]);
    }
}
