<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Files\Models\SftpAccount;
use Livewire\Component;
use Livewire\WithPagination;

final class SftpInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $accounts = SftpAccount::query()
            ->where('team_id', auth()->user()?->current_team_id)
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('username', 'like', '%'.trim($this->search).'%')
                    ->orWhere('home_directory', 'like', '%'.trim($this->search).'%');
            }))
            ->latest()
            ->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-files-livewire::components.sftp-inventory', ['accounts' => $accounts]);
    }
}
