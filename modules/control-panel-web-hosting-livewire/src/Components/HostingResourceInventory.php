<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Actions\CheckApplicationHealth;
use Liberu\ControlPanel\WebHosting\Actions\UpdateHostedApplication;
use Liberu\ControlPanel\WebHosting\Actions\UpdateVirtualHost;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostingLog;
use Liberu\ControlPanel\WebHosting\Models\Redirect;
use Liberu\ControlPanel\WebHosting\Models\RuntimeVersion;
use Liberu\ControlPanel\WebHosting\Models\SslCertificate;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;
use Liberu\ControlPanel\WebHosting\Models\WebServer;
use Livewire\Component;

final class HostingResourceInventory extends Component
{
    public int $perPage = 25;

    /** @var array<string, array<string, mixed>> */
    public array $applicationEdits = [];

    /** @var array<string, array<string, mixed>> */
    public array $virtualHostEdits = [];

    public function checkApplication(string $applicationId, CheckApplicationHealth $check): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        $application = HostedApplication::query()->whereKey($applicationId)->where('team_id', $teamId)->firstOrFail();
        $check->execute($application);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateApplication(string $applicationId, ?array $attributes, UpdateHostedApplication $update): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $application = HostedApplication::query()->whereKey($applicationId)->where('team_id', $teamId)->firstOrFail();
        $attributes ??= $this->applicationEdits[$applicationId] ?? [];
        validator($attributes, [
            'domain_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:wordpress,laravel,static,nodejs,custom'],
            'version' => ['nullable', 'string', 'max:80'],
            'document_root' => ['required', 'string', 'starts_with:/', 'max:2048'],
            'config' => ['nullable', 'array'],
        ])->validate();
        $update->execute($application, $attributes);
        unset($this->applicationEdits[$applicationId]);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateVirtualHost(string $virtualHostId, ?array $attributes, UpdateVirtualHost $update): void
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $virtualHost = VirtualHost::query()->whereKey($virtualHostId)->whereHas('domain', fn ($query) => $query->where('team_id', $teamId))->with('domain')->firstOrFail();
        $attributes ??= $this->virtualHostEdits[$virtualHostId] ?? [];
        validator($attributes, [
            'domain_id' => ['required', 'uuid'],
            'server' => ['required', 'in:nginx,apache'],
            'runtime' => ['nullable', 'string', 'max:80'],
            'document_root' => ['required', 'string', 'starts_with:/', 'max:2048'],
            'active' => ['sometimes', 'boolean'],
        ])->validate();
        $update->execute($virtualHost, $attributes);
        unset($this->virtualHostEdits[$virtualHostId]);
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
            'virtualHosts' => VirtualHost::query()->whereHas('domain', fn ($query) => $query->where('team_id', $teamId))->with('domain')->latest()->limit(10)->get(),
            'logs' => HostingLog::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100)),
        ]);
    }
}
