<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Queries\ListGitDeployments;
use Livewire\Component;

final class GitDeploymentInventory extends Component
{
    public int $perPage = 25;

    public function render(ListGitDeployments $deployments): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-web-hosting-livewire::components.git-deployment-inventory', [
            'deployments' => $deployments->execute($teamId, min(max($this->perPage, 1), 100)),
        ]);
    }
}
