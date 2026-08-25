<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Mail\Queries\ListMailAccounts;
use Livewire\Component;
use Livewire\WithPagination;

final class MailInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(ListMailAccounts $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-mail-livewire::components.mail-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100), $this->search)]);
    }
}
