<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Databases\Queries\ListDatabaseBackups;
use Livewire\Component;

final class BackupInventory extends Component
{
    public int $perPage = 25;

    public function render(ListDatabaseBackups $list): View
    {
        return view('control-panel-databases-livewire::components.backup-inventory', [
            'backups' => $list->execute($this->teamId(), min(max($this->perPage, 1), 100)),
        ]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
