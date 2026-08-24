<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Dns\Models\Zone;
use Livewire\Component;

final class ZoneInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $zones = Zone::query()->with('records')->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-dns-livewire::components.zone-inventory', ['zones' => $zones]);
    }
}
