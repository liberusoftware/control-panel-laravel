<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAccount;
use Liberu\ControlPanel\Mail\Models\MailAccount;
use Liberu\ControlPanel\Mail\Queries\ListMailAccounts;
use Livewire\Component;
use Livewire\WithPagination;

final class MailInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    /** @var array<string, array<string, mixed>> */
    public array $edits = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @param array<string, mixed>|null $attributes */
    public function update(string $accountId, ?array $attributes, UpdateMailAccount $update): void
    {
        $account = MailAccount::query()->whereKey($accountId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->edits[$accountId] ?? [];
        validator($attributes, [
            'domain' => ['required', 'string', 'max:253'],
            'address' => ['required', 'string', 'max:255'],
            'quota_bytes' => ['required', 'integer', 'min:0'],
        ])->validate();
        $update->execute($account, $attributes);
        unset($this->edits[$accountId]);
    }

    public function render(ListMailAccounts $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-mail-livewire::components.mail-inventory', ['items' => $list->execute($teamId, min(max($this->perPage, 1), 100), $this->search)]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
