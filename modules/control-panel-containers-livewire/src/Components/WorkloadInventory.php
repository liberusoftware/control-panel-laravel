<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Containers\Actions\DeleteWorkload;
use Liberu\ControlPanel\Containers\Actions\StartWorkload;
use Liberu\ControlPanel\Containers\Actions\StopWorkload;
use Liberu\ControlPanel\Containers\Models\Workload;
use Liberu\ControlPanel\Containers\Queries\ListWorkloads;
use Livewire\Component;

final class WorkloadInventory extends Component
{
    public int $perPage = 25;

    public function start(string $workloadId, StartWorkload $start): void
    {
        $workload = Workload::query()->whereKey($workloadId)->where('team_id', $this->teamId())->firstOrFail();
        $start->execute($workload);
    }

    public function stop(string $workloadId, StopWorkload $stop): void
    {
        $workload = Workload::query()->whereKey($workloadId)->where('team_id', $this->teamId())->firstOrFail();
        $stop->execute($workload);
    }

    public function delete(string $workloadId, DeleteWorkload $delete): void
    {
        $workload = Workload::query()->whereKey($workloadId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($workload);
    }

    public function render(ListWorkloads $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-containers-livewire::components.workload-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100))]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
