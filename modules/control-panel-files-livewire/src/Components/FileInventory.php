<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Livewire\Component;

final class FileInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $files = FileEntry::query()->where('team_id', auth()->user()?->current_team_id)->whereNot('status', 'deleted')->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-files-livewire::components.file-inventory', ['files' => $files]);
    }
}
