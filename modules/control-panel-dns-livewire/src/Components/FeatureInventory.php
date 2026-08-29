<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

abstract class FeatureInventory extends Component
{
    public int $perPage = 25;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    abstract protected function featureName(): string;

    /** @return array<int, string> */
    abstract protected function columns(): array;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $items = ($this->modelClass())::query()
            ->where('team_id', $teamId)
            ->latest()
            ->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-dns-livewire::components.feature-inventory', [
            'featureName' => $this->featureName(),
            'columns' => $this->columns(),
            'items' => $items,
        ]);
    }
}
