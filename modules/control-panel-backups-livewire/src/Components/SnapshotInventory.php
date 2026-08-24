<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;
use Livewire\Component;

final class SnapshotInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $snapshots = BackupSnapshot::query()->with('policy')->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-backups-livewire::components.snapshot-inventory', ['snapshots' => $snapshots]);
    }
}
