<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Databases\Models\Database;
use Livewire\Component;

final class DatabaseInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $databases = Database::query()->with('engine')->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-databases-livewire::components.database-inventory', ['databases' => $databases]);
    }
}
