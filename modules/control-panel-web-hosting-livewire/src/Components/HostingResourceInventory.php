<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\WebHosting\Actions\CheckApplicationHealth;
use Liberu\ControlPanel\WebHosting\Actions\DeleteCronJob;
use Liberu\ControlPanel\WebHosting\Actions\DeleteHostedApplication;
use Liberu\ControlPanel\WebHosting\Actions\DeleteSubdomain;
use Liberu\ControlPanel\WebHosting\Actions\DeleteVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\UpdateCronJob;
use Liberu\ControlPanel\WebHosting\Actions\UpdateHostedApplication;
use Liberu\ControlPanel\WebHosting\Actions\UpdateSubdomain;
use Liberu\ControlPanel\WebHosting\Actions\UpdateVirtualHost;
use Liberu\ControlPanel\WebHosting\Models\CronJob;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostingLog;
use Liberu\ControlPanel\WebHosting\Models\Redirect;
use Liberu\ControlPanel\WebHosting\Models\RuntimeVersion;
use Liberu\ControlPanel\WebHosting\Models\SslCertificate;
use Liberu\ControlPanel\WebHosting\Models\Subdomain;
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

    /** @var array<string, array<string, mixed>> */
    public array $cronJobEdits = [];

    /** @var array<string, array<string, mixed>> */
    public array $subdomainEdits = [];

    /** @param array<string, mixed>|null $attributes */
    public function updateSubdomain(string $subdomainId, ?array $attributes, UpdateSubdomain $update): void
    {
        $item = Subdomain::query()->whereKey($subdomainId)->whereHas('domain', fn ($query) => $query->where('team_id', $this->teamId()))->firstOrFail();
        $attributes ??= $this->subdomainEdits[$subdomainId] ?? [];
        validator($attributes, ['document_root' => ['required', 'string', 'starts_with:/', 'max:2048'], 'php_version' => ['nullable', 'string', 'max:40'], 'active' => ['sometimes', 'boolean'], 'redirect_url' => ['nullable', 'url', 'max:2048'], 'redirect_type' => ['nullable', 'integer', 'in:301,302']])->validate();
        $update->execute($item, $attributes);
        unset($this->subdomainEdits[$subdomainId]);
    }

    public function deleteSubdomain(string $subdomainId, DeleteSubdomain $delete): void
    {
        $item = Subdomain::query()->whereKey($subdomainId)->whereHas('domain', fn ($query) => $query->where('team_id', $this->teamId()))->firstOrFail();
        $delete->execute($item);
        unset($this->subdomainEdits[$subdomainId]);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateCronJob(string $jobId, ?array $attributes, UpdateCronJob $update): void
    {
        $job = CronJob::query()->whereKey($jobId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->cronJobEdits[$jobId] ?? [];
        $attributes = validator($attributes, [
            'name' => ['required', 'string', 'max:160'], 'command' => ['required', 'string', 'max:4096'],
            'schedule' => ['required', 'string', 'max:100'], 'active' => ['sometimes', 'boolean'],
        ])->validate();
        $update->execute($job, $attributes);
        unset($this->cronJobEdits[$jobId]);
    }

    public function deleteCronJob(string $jobId, DeleteCronJob $delete): void
    {
        $job = CronJob::query()->whereKey($jobId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($job);
        unset($this->cronJobEdits[$jobId]);
    }

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

    public function deleteApplication(string $applicationId, DeleteHostedApplication $delete): void
    {
        $application = HostedApplication::query()->whereKey($applicationId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($application);
        unset($this->applicationEdits[$applicationId]);
    }

    public function deleteVirtualHost(string $virtualHostId, DeleteVirtualHost $delete): void
    {
        $virtualHost = VirtualHost::query()->whereKey($virtualHostId)->whereHas('domain', fn ($query) => $query->where('team_id', $this->teamId()))->firstOrFail();
        $delete->execute($virtualHost);
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
            'cronJobs' => CronJob::query()->where('team_id', $teamId)->with('domain')->latest()->limit(25)->get(),
            'subdomains' => Subdomain::query()->whereHas('domain', fn ($query) => $query->where('team_id', $teamId))->with('domain')->latest()->limit(25)->get(),
        ]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
