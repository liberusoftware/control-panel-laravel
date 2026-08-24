<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Livewire\Component;

final class DomainInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $domains = Domain::query()->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-web-hosting-livewire::components.domain-inventory', ['domains' => $domains]);
    }
}
