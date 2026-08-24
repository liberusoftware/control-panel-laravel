<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\OsAdapters\Queries\ListOsAdapters;
use Livewire\Component;

final class OsAdapterInventory extends Component
{
    public int $perPage = 25;

    public function render(ListOsAdapters $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-os-adapters-livewire::components.os-adapter-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100))]);
    }
}
