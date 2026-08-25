<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Security\Models\ComplianceStatus;
use Livewire\Component;
use Livewire\WithPagination;

final class ComplianceInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $statuses = ComplianceStatus::query()->where('team_id', $teamId)->latest('assessed_at')->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-security-livewire::components.compliance-inventory', ['statuses' => $statuses]);
    }
}
