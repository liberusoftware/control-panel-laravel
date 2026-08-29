<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Databases\Actions\ActivateDatabase;
use Liberu\ControlPanel\Databases\Actions\ArchiveDatabase;
use Liberu\ControlPanel\Databases\Actions\DeleteDatabase;
use Liberu\ControlPanel\Databases\Actions\SuspendDatabase;
use Liberu\ControlPanel\Databases\Actions\UpdateDatabase;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\Databases\Queries\ListDatabases;
use Livewire\Component;
use Livewire\WithPagination;

final class DatabaseInventory extends Component
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

    public function activate(string $databaseId, ActivateDatabase $activate): void
    {
        $database = Database::query()
            ->whereKey($databaseId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $activate->execute($database);
    }

    public function suspend(string $databaseId, SuspendDatabase $suspend): void
    {
        $database = Database::query()->whereKey($databaseId)->where('team_id', $this->teamId())->firstOrFail();
        $suspend->execute($database);
    }

    public function archive(string $databaseId, ArchiveDatabase $archive): void
    {
        $database = Database::query()->whereKey($databaseId)->where('team_id', $this->teamId())->firstOrFail();
        $archive->execute($database);
    }

    public function delete(string $databaseId, DeleteDatabase $delete): void
    {
        $database = Database::query()->whereKey($databaseId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($database);
    }

    /** @param array<string, mixed>|null $attributes */
    public function update(string $databaseId, ?array $attributes, UpdateDatabase $update): void
    {
        $database = Database::query()->whereKey($databaseId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->edits[$databaseId] ?? [];
        validator($attributes, [
            'name' => ['required', 'string', 'max:128'],
            'engine_id' => ['required', 'uuid'],
            'account_id' => ['nullable', 'string', 'max:255'],
            'charset' => ['required', 'string', 'max:40'],
            'collation' => ['required', 'string', 'max:80'],
            'metadata' => ['nullable', 'array'],
        ])->validate();
        $update->execute($database, $attributes);
        unset($this->edits[$databaseId]);
    }

    public function render(ListDatabases $list): View
    {
        $databases = $list->execute($this->teamId(), min(max($this->perPage, 1), 100), $this->search);

        return view('control-panel-databases-livewire::components.database-inventory', ['databases' => $databases]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
