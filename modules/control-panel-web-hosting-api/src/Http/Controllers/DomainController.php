<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\WebHosting\Actions\CreateDomain;
use Liberu\ControlPanel\WebHosting\Actions\CreateVirtualHost;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Queries\ListDomains;
use Liberu\ControlPanel\WebHosting\Actions\CreateRedirect;
use Liberu\ControlPanel\WebHosting\Actions\RequestCertificate;

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

    public function virtualHost(Request $request, Domain $domain, CreateVirtualHost $create): JsonResponse
    {
        abort_unless((string) $domain->team_id === (string) $request->user()?->current_team_id, 404);
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

    private static function resource(Domain $domain): array
    {
        return ['id' => $domain->getKey(), 'type' => 'control-panel-domain', 'attributes' => $domain->only(['hostname', 'status', 'account_id', 'metadata'])];
    }

    private function assertTeam(Request $request, Domain $domain): void
    {
        abort_unless((string) $domain->team_id === (string) $request->user()?->current_team_id, 404);
    }
}
