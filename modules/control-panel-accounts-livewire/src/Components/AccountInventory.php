<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsLivewire\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Accounts\Actions\ActivateAccount;
use Liberu\ControlPanel\Accounts\Actions\ArchiveAccount;
use Liberu\ControlPanel\Accounts\Actions\SuspendAccount;
use Liberu\ControlPanel\Accounts\Models\Account;
use Livewire\Component;
use Livewire\WithPagination;

final class AccountInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public string $suspensionReason = '';

    public function render(): View
    {
        $accounts = Account::query()
            ->where('team_id', auth()->user()?->current_team_id)
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                $query->where('name', 'like', '%'.trim($this->search).'%')
                    ->orWhere('owner_id', 'like', '%'.trim($this->search).'%');
            }))
            ->latest()
            ->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-accounts-livewire::components.account-inventory', ['accounts' => $accounts]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function suspend(string $accountId, SuspendAccount $suspend): void
    {
        $account = Account::query()
            ->whereKey($accountId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $this->validate(['suspensionReason' => ['required', 'string', 'max:1000']]);
        $suspend->execute($account, $this->suspensionReason);
        $this->reset('suspensionReason');
    }

    public function activate(string $accountId, ActivateAccount $activate): void
    {
        $account = Account::query()
            ->whereKey($accountId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $activate->execute($account);
    }

    public function archive(string $accountId, ArchiveAccount $archive): void
    {
        $account = Account::query()
            ->whereKey($accountId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $archive->execute($account);
    }
}
