<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Dns\Models\DnsCheck;
use Liberu\ControlPanel\Dns\Models\DnsTemplate;
use Livewire\Component;

final class DnsFeatureInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;

        return view('control-panel-dns-livewire::components.dns-feature-inventory', ['templates' => DnsTemplate::query()->where('team_id', $teamId)->latest()->limit(10)->get(), 'checks' => DnsCheck::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }
}
