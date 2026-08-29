<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ApiAutomation\Actions\RevokeApiCredential;
use Liberu\ControlPanel\ApiAutomation\Models\ApiCredential;
use Livewire\Component;
use Livewire\WithPagination;

final class CredentialInventory extends Component
{
    use WithPagination;

    public function revoke(string $credentialId, RevokeApiCredential $revoke): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $credential = ApiCredential::query()->whereKey($credentialId)->where('team_id', $teamId)->firstOrFail();

        $revoke->execute($credential);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-api-and-automation-livewire::components.credential-inventory', [
            'credentials' => ApiCredential::query()->where('team_id', $teamId)->latest()->paginate(25),
        ]);
    }
}
