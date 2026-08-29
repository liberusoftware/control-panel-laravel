<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingApi\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\ArchiveDomain;
use Liberu\ControlPanel\WebHosting\Actions\CheckApplicationHealth;
use Liberu\ControlPanel\WebHosting\Actions\CheckWordPressUpdates;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateRedirect;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Actions\RegisterGitDeployment;
use Liberu\ControlPanel\WebHosting\Actions\RegisterHostingResource;
use Liberu\ControlPanel\WebHosting\Actions\RequestCertificate;
use Liberu\ControlPanel\WebHosting\Actions\RequestGitDeployment;
use Liberu\ControlPanel\WebHosting\Actions\SavePhpConfiguration;
use Liberu\ControlPanel\WebHosting\Actions\SuspendDomain;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;
use Liberu\ControlPanel\WebHosting\Models\HostedApplication;
use Liberu\ControlPanel\WebHosting\Models\HostingLog;
use Liberu\ControlPanel\WebHosting\Models\PhpConfiguration;
use Liberu\ControlPanel\WebHosting\Models\Redirect;
use Liberu\ControlPanel\WebHosting\Models\RuntimeVersion;
use Liberu\ControlPanel\WebHosting\Models\SslCertificate;
use Liberu\ControlPanel\WebHosting\Models\VirtualHost;
use Liberu\ControlPanel\WebHosting\Models\WebServer;
use Liberu\ControlPanel\WebHosting\Queries\ListDomains;
use Liberu\ControlPanel\WebHosting\Queries\ListGitDeployments;

final class DomainController
{
    public function index(Request $request, ListDomains $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $domains = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json([
            'data' => $domains->through(static fn (Domain $domain): array => self::resource($domain)),
            'meta' => ['current_page' => $domains->currentPage(), 'per_page' => $domains->perPage(), 'total' => $domains->total()],
        ]);
    }

    public function store(Request $request, CreateDomain $create): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate([
            'hostname' => ['required', 'string', 'max:253'],
            'account_id' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $domain = $create->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($domain)], 201);
    }

    public function activate(Request $request, Domain $domain, ActivateDomain $activate): JsonResponse
    {
        $this->assertTeam($request, $domain);

        return response()->json(['data' => self::resource($activate->execute($domain))]);
    }

    public function suspend(Request $request, Domain $domain, SuspendDomain $suspend): JsonResponse
    {
        $this->assertTeam($request, $domain);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => self::resource($suspend->execute($domain, $data['reason']))]);
    }

    public function archive(Request $request, Domain $domain, ArchiveDomain $archive): JsonResponse
    {
        $this->assertTeam($request, $domain);

        return response()->json(['data' => self::resource($archive->execute($domain))]);
    }

    public function virtualHost(Request $request, Domain $domain, CreateVirtualHost $create): JsonResponse
    {
        $this->assertTeam($request, $domain);
        $data = $request->validate(['node_id' => ['required', 'uuid'], 'server' => ['required', 'in:nginx,apache'], 'runtime' => ['required', 'string', 'max:80'], 'document_root' => ['required', 'string', 'max:1024'], 'desired_state' => ['nullable', 'array']]);
        $host = $create->execute($domain, $data);

        return response()->json(['data' => ['id' => $host->getKey(), 'type' => 'control-panel-virtual-host', 'attributes' => $host->only(['domain_id', 'node_id', 'server', 'runtime', 'document_root', 'desired_state', 'active'])]], 201);
    }

    public function redirect(Request $request, Domain $domain, CreateRedirect $create): JsonResponse
    {
        $this->assertTeam($request, $domain);
        $data = $request->validate(['source' => ['required', 'string', 'max:1024'], 'destination' => ['required', 'string', 'max:2048'], 'status_code' => ['nullable', 'integer', 'in:301,302,307,308']]);
        $redirect = $create->execute($domain, $data);

        return response()->json(['data' => ['id' => $redirect->getKey(), 'type' => 'control-panel-redirect', 'attributes' => $redirect->only(['domain_id', 'source', 'destination', 'status_code', 'active'])]], 201);
    }

    public function certificate(Request $request, Domain $domain, RequestCertificate $requestCertificate): JsonResponse
    {
        $this->assertTeam($request, $domain);
        $data = $request->validate(['issuer' => ['nullable', 'string', 'max:120'], 'auto_renew' => ['sometimes', 'boolean'], 'metadata' => ['nullable', 'array']]);
        $certificate = $requestCertificate->execute($domain, $data);

        return response()->json(['data' => ['id' => $certificate->getKey(), 'type' => 'control-panel-ssl-certificate', 'attributes' => $certificate->only(['domain_id', 'issuer', 'status', 'auto_renew', 'expires_at'])]], 202);
    }

    public function resourceRecord(Request $request, RegisterHostingResource $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:runtime,server,log,application'], 'payload' => ['required', 'array']]);
        $item = $register->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-web-hosting-'.$data['kind'], 'attributes' => $item->toArray()]], 201);
    }

    public function resources(Request $request, string $kind): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $models = [
            'runtime' => RuntimeVersion::class, 'server' => WebServer::class, 'log' => HostingLog::class,
            'application' => HostedApplication::class, 'redirect' => Redirect::class, 'certificate' => SslCertificate::class,
            'virtual-host' => VirtualHost::class,
        ];
        abort_unless(isset($models[$kind]), 404, 'Unsupported hosting resource.');
        $query = $models[$kind]::query();
        if ($kind === 'virtual-host') {
            $query->whereHas('domain', fn (Builder $domain) => $domain->where('team_id', $teamId));
        } else {
            $query->where('team_id', $teamId);
        }
        $page = $query->latest()->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json(['data' => $page->through(fn (Model $model): array => ['id' => $model->getKey(), 'type' => 'control-panel-web-hosting-'.$kind, 'attributes' => $model->toArray()]), 'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function applications(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $page = HostedApplication::query()->where('team_id', $teamId)->with('domain')->latest()->paginate($this->perPage($request));

        return response()->json(['data' => $page->through(fn (HostedApplication $application): array => self::applicationResource($application)), 'meta' => ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function application(Request $request, RegisterHostingResource $register): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'domain_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:wordpress,laravel,static,nodejs,custom'], 'version' => ['nullable', 'string', 'max:80'],
            'document_root' => ['required', 'string', 'starts_with:/', 'max:2048'], 'config' => ['nullable', 'array'],
        ]);
        $domain = Domain::query()->where('team_id', $teamId)->findOrFail($data['domain_id']);
        $application = $register->execute([...$data, 'kind' => 'application', 'team_id' => $teamId, 'domain_id' => $domain->getKey()]);

        return response()->json(['data' => self::applicationResource($application)], 201);
    }

    public function applicationPerformance(Request $request, HostedApplication $application): JsonResponse
    {
        $this->assertApplicationTeam($request, $application);
        $hours = min(max($request->integer('hours', 24), 1), 720);
        $metrics = $application->performanceMetrics()->where('checked_at', '>=', now()->subHours($hours))->oldest('checked_at')->get();
        $total = $metrics->count();

        return response()->json(['data' => $metrics, 'meta' => ['hours' => $hours, 'total_checks' => $total, 'uptime_percentage' => $total === 0 ? null : round(($metrics->where('healthy', true)->count() / $total) * 100, 2), 'average_response_time' => $total === 0 ? null : round((float) $metrics->avg('response_time_ms'), 2)]]);
    }

    public function applicationHealth(Request $request, HostedApplication $application, CheckApplicationHealth $check): JsonResponse
    {
        $this->assertApplicationTeam($request, $application);

        return response()->json(['data' => $check->execute($application)], 201);
    }

    public function wordpressUpdate(Request $request, HostedApplication $application, CheckWordPressUpdates $check): JsonResponse
    {
        $this->assertApplicationTeam($request, $application);

        return response()->json(['data' => $check->execute($application)]);
    }

    public function deployments(Request $request, ListGitDeployments $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $deployments = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $deployments->through(static fn (GitDeployment $deployment): array => self::deploymentResource($deployment)), 'meta' => ['current_page' => $deployments->currentPage(), 'per_page' => $deployments->perPage(), 'total' => $deployments->total()]]);
    }

    public function deployment(Request $request, Domain $domain, RegisterGitDeployment $register): JsonResponse
    {
        $this->assertTeam($request, $domain);
        $data = $request->validate([
            'repository_url' => ['required', 'string', 'max:2048'], 'branch' => ['nullable', 'string', 'max:255'],
            'deploy_path' => ['required', 'string', 'starts_with:/', 'max:1024'], 'deploy_key' => ['nullable', 'string'],
            'use_oauth' => ['sometimes', 'boolean'], 'connected_account_id' => ['nullable', 'string', 'max:255'],
            'container_id' => ['nullable', 'string', 'max:255'], 'kubernetes_pod_name' => ['nullable', 'string', 'max:255'],
            'kubernetes_namespace' => ['nullable', 'string', 'max:255'], 'build_command' => ['nullable', 'string', 'max:1024'],
            'deploy_command' => ['nullable', 'string', 'max:1024'], 'auto_deploy' => ['sometimes', 'boolean'],
        ]);
        $deployment = $register->execute($domain, $data);

        return response()->json(['data' => self::deploymentResource($deployment)], 201);
    }

    public function deploy(Request $request, string $deployment, RequestGitDeployment $requestDeployment): JsonResponse
    {
        $teamId = $this->teamId($request);
        $item = GitDeployment::query()->whereKey($deployment)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::deploymentResource($requestDeployment->execute($item))], 202);
    }

    public function phpConfiguration(Request $request, Domain $domain, SavePhpConfiguration $save): JsonResponse
    {
        $this->assertTeam($request, $domain);
        $data = $request->validate([
            'php_version' => ['required', 'string', 'in:7.4,8.0,8.1,8.2,8.3,8.4,8.5'],
            'memory_limit' => ['nullable', 'integer', 'min:1', 'max:1048576'], 'upload_max_filesize' => ['nullable', 'integer', 'min:1', 'max:1048576'],
            'post_max_size' => ['nullable', 'integer', 'min:1', 'max:1048576'], 'max_execution_time' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'max_input_time' => ['nullable', 'integer', 'min:1', 'max:86400'], 'max_input_vars' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'display_errors' => ['sometimes', 'boolean'], 'short_open_tag' => ['sometimes', 'boolean'],
            'error_reporting' => ['nullable', 'string', 'max:255'], 'session_save_path' => ['nullable', 'string', 'max:1024'],
            'custom_settings' => ['nullable', 'array'],
        ]);
        $configuration = $save->execute($domain, $data);

        return response()->json(['data' => self::phpConfigurationResource($configuration)]);
    }

    private static function resource(Domain $domain): array
    {
        return ['id' => $domain->getKey(), 'type' => 'control-panel-domain', 'attributes' => $domain->only(['hostname', 'status', 'account_id', 'metadata'])];
    }

    private function assertTeam(Request $request, Domain $domain): void
    {
        abort_if($request->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $domain->team_id === (string) $request->user()?->current_team_id, 404);
    }

    private function assertApplicationTeam(Request $request, HostedApplication $application): void
    {
        abort_if($request->user()?->current_team_id === null, 403, 'A current team is required.');
        abort_unless((string) $application->team_id === (string) $request->user()?->current_team_id, 404);
    }

    private function teamId(Request $request): string
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }

    /** @return array<string, mixed> */
    private static function applicationResource(HostedApplication $application): array
    {
        return ['id' => $application->getKey(), 'type' => 'control-panel-hosted-application', 'attributes' => $application->only(['domain_id', 'name', 'type', 'version', 'document_root', 'status', 'config']) + ['health_status' => $application->healthStatus()]];
    }

    /** @return array<string, mixed> */
    private static function deploymentResource(GitDeployment $deployment): array
    {
        return ['id' => $deployment->getKey(), 'type' => 'control-panel-git-deployment', 'attributes' => $deployment->only(['domain_id', 'repository_url', 'repository_type', 'branch', 'deploy_path', 'use_oauth', 'status', 'auto_deploy', 'last_deployed_at', 'last_commit_hash'])];
    }

    /** @return array<string, mixed> */
    private static function phpConfigurationResource(PhpConfiguration $configuration): array
    {
        return ['id' => $configuration->getKey(), 'type' => 'control-panel-php-configuration', 'attributes' => $configuration->only(['domain_id', 'php_version', 'memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'max_input_time', 'max_input_vars', 'display_errors', 'short_open_tag', 'error_reporting', 'session_save_path', 'custom_settings'])];
    }
}
