<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ApiAutomation\Queries\ListAutomations;
use Livewire\Component;
use Livewire\WithPagination;

final class AutomationInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(ListAutomations $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-api-and-automation-livewire::components.automation-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100), $this->search)]);
    }
}
