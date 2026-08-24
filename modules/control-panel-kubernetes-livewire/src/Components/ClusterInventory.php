<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Kubernetes\Queries\ListClusters;
use Livewire\Component;

final class ClusterInventory extends Component
{
    public int $perPage = 25;

    public function render(ListClusters $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-kubernetes-livewire::components.cluster-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100))]);
    }
}
