<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Databases\Queries\ListDatabases;
use Livewire\Component;

final class DatabaseInventory extends Component
{
    public int $perPage = 25;

    public function render(ListDatabases $list): View
    {
        $databases = $list->execute(auth()->user()?->current_team_id, min(max($this->perPage, 1), 100));

        return view('control-panel-databases-livewire::components.database-inventory', ['databases' => $databases]);
    }
}
