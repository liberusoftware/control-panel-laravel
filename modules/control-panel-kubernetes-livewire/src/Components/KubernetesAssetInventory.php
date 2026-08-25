<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesResource;
use Livewire\Component;

final class KubernetesAssetInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;

        return view('control-panel-kubernetes-livewire::components.kubernetes-asset-inventory', ['nodes' => KubernetesNode::query()->where('team_id', $teamId)->latest()->limit(10)->get(), 'resources' => KubernetesResource::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
