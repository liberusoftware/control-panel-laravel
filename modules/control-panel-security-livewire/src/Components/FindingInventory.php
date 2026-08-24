<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Security\Queries\ListFindings;
use Livewire\Component;

final class FindingInventory extends Component
{
    public int $perPage = 25;

    public function render(ListFindings $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-security-livewire::components.finding-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100))]);
    }
}
