<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ContainersLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Containers\Models\ContainerImage;
use Liberu\ControlPanel\Containers\Models\ContainerLifecycle;
use Liberu\ControlPanel\Containers\Models\ContainerLimit;
use Liberu\ControlPanel\Containers\Models\ContainerNetwork;
use Liberu\ControlPanel\Containers\Models\ContainerRegistry;
use Liberu\ControlPanel\Containers\Models\ContainerResource;
use Liberu\ControlPanel\Containers\Models\ContainerSecret;
use Liberu\ControlPanel\Containers\Models\ContainerVolume;
use Livewire\Component;

final class ContainerAssetInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $models = ['images' => ContainerImage::class, 'registries' => ContainerRegistry::class, 'networks' => ContainerNetwork::class, 'volumes' => ContainerVolume::class, 'secrets' => ContainerSecret::class, 'limits' => ContainerLimit::class, 'lifecycle' => ContainerLifecycle::class];
        $assets = collect($models)->mapWithKeys(fn (string $model, string $key): array => [$key => $model::query()->where('team_id', $teamId)->latest()->limit(10)->get()]);

        return view('control-panel-containers-livewire::components.container-asset-inventory', ['assets' => $assets, 'resources' => ContainerResource::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
