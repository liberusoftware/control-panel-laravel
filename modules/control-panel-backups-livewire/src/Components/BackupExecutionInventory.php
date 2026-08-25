<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Models\BackupExecution;
use Livewire\Component;

final class BackupExecutionInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $executions = BackupExecution::query()->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-backups-livewire::components.backup-execution-inventory', ['executions' => $executions]);
    }
}
