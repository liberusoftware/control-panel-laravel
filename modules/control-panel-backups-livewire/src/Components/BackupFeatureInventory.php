<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Backups\Models\BackupEncryption;
use Liberu\ControlPanel\Backups\Models\BackupRestore;
use Liberu\ControlPanel\Backups\Models\OffsiteTransfer;
use Livewire\Component;

final class BackupFeatureInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $limit = min(max($this->perPage, 1), 100);

        return view('control-panel-backups-livewire::components.backup-feature-inventory', [
            'encryptions' => BackupEncryption::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
            'restores' => BackupRestore::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
            'transfers' => OffsiteTransfer::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
        ]);
    }
}
