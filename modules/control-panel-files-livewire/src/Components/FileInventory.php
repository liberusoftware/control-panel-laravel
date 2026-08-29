<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Files\Actions\DeleteFile;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Liberu\ControlPanel\Files\Queries\ListFiles;
use Livewire\Component;
use Livewire\WithPagination;

final class FileInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $fileId, DeleteFile $delete): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $file = FileEntry::query()->whereKey($fileId)->where('team_id', $teamId)->firstOrFail();
        $delete->execute($file);
    }

    public function render(ListFiles $list): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $files = $list->execute((string) $teamId, $this->perPage, $this->search);

        return view('control-panel-files-livewire::components.file-inventory', ['files' => $files]);
    }
}
