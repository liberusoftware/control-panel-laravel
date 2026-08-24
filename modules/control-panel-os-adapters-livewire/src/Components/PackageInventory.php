<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\OsAdapters\Models\OsPackage;
use Livewire\Component;

final class PackageInventory extends Component
{
    public int $perPage = 25;
    public function render(): View
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        return view('control-panel-os-adapters-livewire::components.package-inventory', ['packages' => OsPackage::query()->where('team_id', auth()->user()->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
