<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Files\Models\SftpAccount;
use Livewire\Component;

final class SftpInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $accounts = SftpAccount::query()->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));
        return view('control-panel-files-livewire::components.sftp-inventory', ['accounts' => $accounts]);
    }
}
