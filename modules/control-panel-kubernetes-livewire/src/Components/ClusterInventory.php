<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Kubernetes\Actions\ArchiveCluster;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\Kubernetes\Queries\ListClusters;
use Livewire\Component;

final class ClusterInventory extends Component
{
    public int $perPage = 25;

    public function suspend(string $clusterId, SuspendCluster $suspend): void
    {
        $cluster = Cluster::query()->whereKey($clusterId)->where('team_id', $this->teamId())->firstOrFail();
        $suspend->execute($cluster);
    }

    public function archive(string $clusterId, ArchiveCluster $archive): void
    {
        $cluster = Cluster::query()->whereKey($clusterId)->where('team_id', $this->teamId())->firstOrFail();
        $archive->execute($cluster);
    }

    public function render(ListClusters $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-kubernetes-livewire::components.cluster-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100))]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
