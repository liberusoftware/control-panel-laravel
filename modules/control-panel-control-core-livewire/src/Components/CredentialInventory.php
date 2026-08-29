<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ControlCore\Actions\ExpireNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Livewire\Component;
use Livewire\WithPagination;

final class CredentialInventory extends Component
{
    use WithPagination;

    public function revoke(string $credentialId, RevokeNodeCredential $revoke): void
    {
        $credential = NodeCredential::query()
            ->whereKey($credentialId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $revoke->execute($credential);
    }

    public function expire(string $credentialId, ExpireNodeCredential $expire): void
    {
        $credential = NodeCredential::query()
            ->whereKey($credentialId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $expire->execute($credential);
    }

    public function render(): View
    {
        $credentials = NodeCredential::query()->where('team_id', $this->teamId())->latest()->paginate(25);

        return view('control-panel-control-core-livewire::components.credential-inventory', ['credentials' => $credentials]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
