<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Dns\Models\Zone;
use Livewire\Component;
use Livewire\WithPagination;

final class ZoneInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $zones = Zone::query()->with('records')->where('team_id', $teamId)->when(trim($this->search) !== '', fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'))->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-dns-livewire::components.zone-inventory', ['zones' => $zones]);
    }
}
