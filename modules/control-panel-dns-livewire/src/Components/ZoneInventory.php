<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Dns\Actions\ArchiveZone;
use Liberu\ControlPanel\Dns\Actions\SuspendZone;
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

    public function suspend(string $zoneId, SuspendZone $suspend): void
    {
        $zone = Zone::query()->whereKey($zoneId)->where('team_id', $this->teamId())->firstOrFail();
        $suspend->execute($zone);
    }

    public function archive(string $zoneId, ArchiveZone $archive): void
    {
        $zone = Zone::query()->whereKey($zoneId)->where('team_id', $this->teamId())->firstOrFail();
        $archive->execute($zone);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $zones = Zone::query()->with('records')->where('team_id', $teamId)->when(trim($this->search) !== '', fn ($query) => $query->where('domain', 'like', '%'.trim($this->search).'%'))->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-dns-livewire::components.zone-inventory', ['zones' => $zones]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
