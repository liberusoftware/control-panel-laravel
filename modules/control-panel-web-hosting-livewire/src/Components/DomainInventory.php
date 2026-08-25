<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\ArchiveDomain;
use Liberu\ControlPanel\WebHosting\Actions\SuspendDomain;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Livewire\Component;
use Livewire\WithPagination;

final class DomainInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function activate(string $domainId, ActivateDomain $activate): void
    {
        $activate->execute($this->tenantDomain($domainId));
    }

    public function suspend(string $domainId, string $reason, SuspendDomain $suspend): void
    {
        $suspend->execute($this->tenantDomain($domainId), $reason);
    }

    public function archive(string $domainId, ArchiveDomain $archive): void
    {
        $archive->execute($this->tenantDomain($domainId));
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $domains = Domain::query()->where('team_id', $teamId)->when(trim($this->search) !== '', fn ($query) => $query->where('hostname', 'like', '%'.trim($this->search).'%'))->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-web-hosting-livewire::components.domain-inventory', ['domains' => $domains]);
    }

    private function tenantDomain(string $domainId): Domain
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return Domain::query()->whereKey($domainId)->where('team_id', $teamId)->firstOrFail();
    }
}
