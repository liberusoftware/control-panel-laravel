<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Security\Models\IntrusionControl;
use Liberu\ControlPanel\Security\Models\MalwareScan;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\Security\Models\PatchRecord;
use Liberu\ControlPanel\Security\Models\SecretRecord;
use Livewire\Component;

final class SecurityOperationsInventory extends Component
{
    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-security-livewire::components.security-operations-inventory', [
            'patches' => PatchRecord::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'policies' => MfaRbacPolicy::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'secrets' => SecretRecord::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'malwareScans' => MalwareScan::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'intrusionControls' => IntrusionControl::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
        ]);
    }
}
