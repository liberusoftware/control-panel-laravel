<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Actions\RequestRestore;
use Liberu\ControlPanel\Backups\Actions\VerifySnapshot;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;
use Livewire\Component;

final class SnapshotInventory extends Component
{
    public int $perPage = 25;

    public string $restoreTarget = '';

    public string $checksum = '';

    public function verify(string $snapshotId, VerifySnapshot $verify): void
    {
        $snapshot = BackupSnapshot::query()->whereKey($snapshotId)->where('team_id', auth()->user()?->current_team_id)->firstOrFail();
        $this->validate(['checksum' => ['required', 'string', 'max:255']]);
        $verify->execute($snapshot, $this->checksum);
        $this->reset('checksum');
    }

    public function restore(string $snapshotId, RequestRestore $restore): void
    {
        $snapshot = BackupSnapshot::query()->whereKey($snapshotId)->where('team_id', auth()->user()?->current_team_id)->firstOrFail();
        $this->validate(['restoreTarget' => ['required', 'string', 'max:1024']]);
        $restore->execute($snapshot, (string) auth()->user()?->current_team_id, $this->restoreTarget);
        $this->reset('restoreTarget');
    }

    public function render(): View
    {
        $snapshots = BackupSnapshot::query()->with('policy')->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-backups-livewire::components.snapshot-inventory', ['snapshots' => $snapshots]);
    }
}
