<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Containers\Models\ContainerImage;
use Liberu\ControlPanel\Containers\Models\ContainerResource;
use Livewire\Component;

final class ContainerAssetInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;

        return view('control-panel-containers-livewire::components.container-asset-inventory', ['images' => ContainerImage::query()->where('team_id', $teamId)->latest()->limit(10)->get(), 'resources' => ContainerResource::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
