<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Dns\Actions\CheckDnsPropagation;
use Liberu\ControlPanel\Dns\Actions\CheckDnsResolution;
use Liberu\ControlPanel\Dns\Models\DnsCheck;
use Liberu\ControlPanel\Dns\Models\DnsTemplate;
use Liberu\ControlPanel\Dns\Models\Zone;
use Livewire\Component;

final class DnsFeatureInventory extends Component
{
    public int $perPage = 25;

    public function checkResolution(string $zoneId, CheckDnsResolution $check): void
    {
        $check->execute(['team_id' => $this->teamId(), 'zone_id' => $zoneId]);
    }

    public function checkPropagation(string $zoneId, CheckDnsPropagation $check): void
    {
        $check->execute(['team_id' => $this->teamId(), 'zone_id' => $zoneId]);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-dns-livewire::components.dns-feature-inventory', ['templates' => DnsTemplate::query()->where('team_id', $teamId)->latest()->limit(10)->get(), 'zones' => Zone::query()->where('team_id', $teamId)->latest()->limit(10)->get(), 'checks' => DnsCheck::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100))]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
