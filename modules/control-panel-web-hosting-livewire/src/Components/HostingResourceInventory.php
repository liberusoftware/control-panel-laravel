<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Actions\CheckApplicationHealth;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostingLog;
use Liberu\ControlPanel\WebHosting\Models\Redirect;
use Liberu\ControlPanel\WebHosting\Models\RuntimeVersion;
use Liberu\ControlPanel\WebHosting\Models\SslCertificate;
use Liberu\ControlPanel\WebHosting\Models\WebServer;
use Livewire\Component;

final class HostingResourceInventory extends Component
{
    public int $perPage = 25;

    public function checkApplication(string $applicationId, CheckApplicationHealth $check): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $application = HostedApplication::query()->whereKey($applicationId)->where('team_id', $teamId)->firstOrFail();
        $check->execute($application);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-web-hosting-livewire::components.hosting-resource-inventory', [
            'runtimes' => RuntimeVersion::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'servers' => WebServer::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'certificates' => SslCertificate::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'redirects' => Redirect::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'applications' => HostedApplication::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'logs' => HostingLog::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100)),
        ]);
    }
}
