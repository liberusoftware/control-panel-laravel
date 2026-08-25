<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Actions\RequestGitDeployment;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;
use Liberu\ControlPanel\WebHosting\Queries\ListGitDeployments;
use Livewire\Component;
use Livewire\WithPagination;

final class GitDeploymentInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public function deploy(string $deploymentId, RequestGitDeployment $requestDeployment): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $deployment = GitDeployment::query()->whereKey($deploymentId)->where('team_id', $teamId)->firstOrFail();
        $requestDeployment->execute($deployment);
    }

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(ListGitDeployments $deployments): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-web-hosting-livewire::components.git-deployment-inventory', [
            'deployments' => $deployments->execute($teamId, min(max($this->perPage, 1), 100), $this->search),
        ]);
    }
}
