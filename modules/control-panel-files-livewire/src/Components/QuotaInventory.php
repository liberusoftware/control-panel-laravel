<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Files\Actions\SetFileQuota;
use Liberu\ControlPanel\Files\Models\FileQuota;
use Livewire\Component;
use Livewire\WithPagination;

final class QuotaInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $ownerId = '';

    public int $limitBytes = 0;

    public function save(SetFileQuota $set): void
    {
        $teamId = $this->teamId();
        $this->validate(['ownerId' => ['nullable', 'string', 'max:255'], 'limitBytes' => ['required', 'integer', 'min:0']]);
        $set->execute(['team_id' => $teamId, 'owner_id' => $this->ownerId !== '' ? $this->ownerId : null, 'limit_bytes' => $this->limitBytes]);
        $this->reset(['ownerId', 'limitBytes']);
        $this->resetPage();
    }

    public function render(): View
    {
        $quotas = FileQuota::query()->where('team_id', $this->teamId())->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-files-livewire::components.quota-inventory', ['quotas' => $quotas]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
