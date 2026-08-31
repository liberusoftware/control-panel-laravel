<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Databases\Models\DatabaseHealthCheck;
use Liberu\ControlPanel\Databases\Models\DatabasePrivilege;
use Liberu\ControlPanel\Databases\Models\DatabaseRemoteAccess;
use Liberu\ControlPanel\Databases\Models\DatabaseUpgrade;
use Liberu\ControlPanel\Databases\Models\DatabaseUser;
use Livewire\Component;

final class DatabaseFeatureInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $limit = min(max($this->perPage, 1), 100);
        $models = [
            'users' => DatabaseUser::class,
            'privileges' => DatabasePrivilege::class,
            'upgrades' => DatabaseUpgrade::class,
            'health checks' => DatabaseHealthCheck::class,
            'remote access' => DatabaseRemoteAccess::class,
        ];
        $features = collect($models)->mapWithKeys(fn (string $model, string $key): array => [$key => $model::query()->where('team_id', $teamId)->latest()->limit($limit)->get()]);

        return view('control-panel-databases-livewire::components.database-feature-inventory', ['features' => $features]);
    }
}
