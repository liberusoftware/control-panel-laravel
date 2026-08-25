<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ControlCore\Actions\DecommissionNode;
use Liberu\ControlPanel\ControlCore\Models\Node;
use Liberu\ControlPanel\ControlCore\Queries\ListNodes;
use Livewire\Component;
use Livewire\WithPagination;

final class NodeInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function decommission(string $nodeId, DecommissionNode $decommission): void
    {
        $node = Node::query()
            ->whereKey($nodeId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $decommission->execute($node);
    }

    public function render(ListNodes $nodes): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $page = $nodes->execute($teamId, min(max($this->perPage, 1), 100), $this->search);

        return view('control-panel-control-core-livewire::components.node-inventory', ['nodes' => $page]);
    }
}
