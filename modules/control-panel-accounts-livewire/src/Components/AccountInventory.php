<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\AccountsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Accounts\Models\Account;
use Livewire\Component;

final class AccountInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $accounts = Account::query()
            ->where('team_id', auth()->user()?->current_team_id)
            ->latest()
            ->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-accounts-livewire::components.account-inventory', ['accounts' => $accounts]);
    }
}
