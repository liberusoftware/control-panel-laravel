<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ApiAutomation\Queries\ListWebhooks;
use Livewire\Component;

final class WebhookInventory extends Component
{
    public int $perPage = 25;

    public function render(ListWebhooks $list): View
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
        return view('control-panel-api-and-automation-livewire::components.webhook-inventory', ['webhooks' => $list->execute(auth()->user()->current_team_id, min(max($this->perPage, 1), 100))]);
    }
}
