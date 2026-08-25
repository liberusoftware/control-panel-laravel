<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Models\PhpConfiguration;
use Livewire\Component;

final class PhpConfigurationInventory extends Component
{
    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-web-hosting-livewire::components.php-configuration-inventory', [
            'configurations' => PhpConfiguration::query()->with('domain')->where('team_id', $teamId)->latest('updated_at')->paginate(25),
        ]);
    }
}
