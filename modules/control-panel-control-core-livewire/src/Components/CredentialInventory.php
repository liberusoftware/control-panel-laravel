<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Livewire\Component;

final class CredentialInventory extends Component
{
    public function render(): View
    {
        $credentials = NodeCredential::query()->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(25);

        return view('control-panel-control-core-livewire::components.credential-inventory', ['credentials' => $credentials]);
    }
}
