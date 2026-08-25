<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\Security\Queries\ListFindings;
use Livewire\Component;
use Livewire\WithPagination;

final class FindingInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resolve(string $findingId, ResolveSecurityFinding $resolve): void
    {
        $finding = SecurityFinding::query()
            ->whereKey($findingId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();

        $resolve->execute($finding);
    }

    public function render(ListFindings $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-security-livewire::components.finding-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100), $this->search)]);
    }
}
