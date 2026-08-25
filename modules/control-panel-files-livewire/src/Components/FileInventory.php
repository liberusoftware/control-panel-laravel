<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
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

    public function render(ListFiles $list): View
    {
        $files = $list->execute(auth()->user()?->current_team_id, $this->perPage, $this->search);

        return view('control-panel-files-livewire::components.file-inventory', ['files' => $files]);
    }
}
