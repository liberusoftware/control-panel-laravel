<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Security\Actions\UnbanFail2banBan;
use Liberu\ControlPanel\Security\Models\Fail2banBan;
use Liberu\ControlPanel\Security\Models\Fail2banSetting;
use Livewire\Component;

final class Fail2banInventory extends Component
{
    public function unban(string $banId, UnbanFail2banBan $unban): void
    {
        $ban = Fail2banBan::query()->whereKey($banId)->where('team_id', $this->teamId())->firstOrFail();
        $unban->execute($ban);
    }

    public function render(): View
    {
        $teamId = $this->teamId();

        return view('control-panel-security-livewire::components.fail2ban-inventory', [
            'settings' => Fail2banSetting::query()->where('team_id', $teamId)->latest()->limit(25)->get(),
            'bans' => Fail2banBan::query()->where('team_id', $teamId)->whereNull('unbanned_at')->latest('banned_at')->limit(50)->get(),
        ]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
