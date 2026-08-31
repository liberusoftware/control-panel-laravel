<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Files\Actions\DeleteSftpAccount;
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

    public function delete(string $accountId, DeleteSftpAccount $delete): void
    {
        $account = SftpAccount::query()->whereKey($accountId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($account);
    }

    public function render(): View
    {
        $teamId = $this->teamId();
        $accounts = SftpAccount::query()
            ->where('team_id', $teamId)
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('username', 'like', '%'.trim($this->search).'%')
                    ->orWhere('home_directory', 'like', '%'.trim($this->search).'%');
            }))
            ->latest()
            ->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-files-livewire::components.sftp-inventory', ['accounts' => $accounts]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
