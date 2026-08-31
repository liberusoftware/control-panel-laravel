<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Kubernetes\Models\HelmRelease;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesAutoscaler;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesClusterView;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesIngress;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNamespace;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesRbacBinding;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesResource;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesStorageClaim;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesUpgrade;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesWorkload;
use Livewire\Component;

final class KubernetesAssetInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $models = ['nodes' => KubernetesNode::class, 'namespaces' => KubernetesNamespace::class, 'rbac' => KubernetesRbacBinding::class, 'workloads' => KubernetesWorkload::class, 'ingresses' => KubernetesIngress::class, 'helm' => HelmRelease::class, 'storage' => KubernetesStorageClaim::class, 'autoscaling' => KubernetesAutoscaler::class, 'upgrades' => KubernetesUpgrade::class, 'cluster views' => KubernetesClusterView::class];
        $assets = collect($models)->mapWithKeys(fn (string $model, string $key): array => [$key => $model::query()->where('team_id', $teamId)->latest()->limit(10)->get()]);

        return view('control-panel-kubernetes-livewire::components.kubernetes-asset-inventory', ['assets' => $assets, 'resources' => KubernetesResource::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
