<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Livewire\Component;

final class PolicyInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');

        return view('control-panel-backups-livewire::components.policy-inventory', ['policies' => BackupPolicy::query()->where('team_id', auth()->user()->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
