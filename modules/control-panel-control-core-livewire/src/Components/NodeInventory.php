<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ControlCore\Queries\ListNodes;
use Livewire\Component;

final class NodeInventory extends Component
{
    public int $perPage = 25;

    public function render(ListNodes $nodes): View
    {
        $page = $nodes->execute(auth()->user()?->current_team_id, min(max($this->perPage, 1), 100));

        return view('control-panel-control-core-livewire::components.node-inventory', ['nodes' => $page]);
    }
}
