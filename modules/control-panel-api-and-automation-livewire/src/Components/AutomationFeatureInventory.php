<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationCommand;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationSchedule;
use Liberu\ControlPanel\ApiAutomation\Models\AutomationTemplate;
use Liberu\ControlPanel\ApiAutomation\Models\BillingProvisioningEvent;
use Livewire\Component;

final class AutomationFeatureInventory extends Component
{
    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-api-and-automation-livewire::components.feature-inventory', ['templates' => AutomationTemplate::where('team_id', $teamId)->latest()->limit(25)->get(), 'schedules' => AutomationSchedule::where('team_id', $teamId)->latest()->limit(25)->get(), 'commands' => AutomationCommand::where('team_id', $teamId)->latest()->limit(25)->get(), 'events' => BillingProvisioningEvent::where('team_id', $teamId)->latest()->limit(25)->get()]);
    }
}
