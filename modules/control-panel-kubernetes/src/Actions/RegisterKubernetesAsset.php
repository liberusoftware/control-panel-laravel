<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Kubernetes\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Kubernetes\Models\HelmRelease;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesAutoscaler;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesClusterView;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesIngress;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNamespace;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesRbacBinding;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesStorageClaim;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesUpgrade;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesWorkload;

final class RegisterKubernetesAsset
{
    public function execute(array $a): Model
    {
        $kind = (string) ($a['kind'] ?? '');
        $map = ['node' => KubernetesNode::class, 'namespace' => KubernetesNamespace::class, 'rbac' => KubernetesRbacBinding::class, 'workload' => KubernetesWorkload::class, 'ingress' => KubernetesIngress::class, 'helm' => HelmRelease::class, 'storage' => KubernetesStorageClaim::class, 'autoscaling' => KubernetesAutoscaler::class, 'upgrade' => KubernetesUpgrade::class, 'cluster-view' => KubernetesClusterView::class];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported Kubernetes asset.']);
        } $a['id'] = $a['id'] ?? (string) Str::uuid();
        $a['team_id'] = $a['team_id'] ?? null;
        if ($kind === 'workload') {
            $a['kind'] = $a['workload_kind'] ?? 'Deployment';
        } else {
            unset($a['kind']);
        }

return $map[$kind]::query()->create($a);
    }
}
