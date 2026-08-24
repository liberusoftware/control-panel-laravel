<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Containers\Queries\ListWorkloads;
use Livewire\Component;

final class WorkloadInventory extends Component
{
    public int $perPage = 25;

    public function render(ListWorkloads $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-containers-livewire::components.workload-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100))]);
    }
}
