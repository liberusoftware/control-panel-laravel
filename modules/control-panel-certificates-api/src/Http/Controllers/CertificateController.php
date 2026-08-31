<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Certificates\Actions\CheckCertificateExpiry;
use Liberu\ControlPanel\Certificates\Actions\ExpireCertificate;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\Actions\RecordCertificateOperation;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\Actions\RegisterCertificateLifecycle;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateDeployment;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateRenewal;
use Liberu\ControlPanel\Certificates\Actions\RevokeCertificate;
use Liberu\ControlPanel\Certificates\Actions\UpdateCertificate;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Queries\ListCertificates;

final class CertificateController
{
    /** @var array<string, list<string>> */
    private const LIFECYCLE_FIELDS = [
        'deployment' => ['certificate_id', 'target_type', 'target_id', 'status', 'deployed_at', 'error', 'metadata'],
        'renewal' => ['certificate_id', 'scheduled_at', 'started_at', 'completed_at', 'status', 'attempts', 'error'],
        'expiry' => ['certificate_id', 'threshold_days', 'status', 'notified_at', 'metadata'],
    ];

    public function index(Request $request, ListCertificates $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $certificates = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $certificates->through(static fn (Certificate $certificate): array => self::resource($certificate)), 'meta' => ['current_page' => $certificates->currentPage(), 'per_page' => $certificates->perPage(), 'total' => $certificates->total()]]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $item = Certificate::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();

        return response()->json(['data' => self::resource($item)]);
    }

    public function update(Request $request, string $id, UpdateCertificate $update): JsonResponse
    {
        $certificate = $this->findForTeam($request, $id);
        $data = $request->validate(['domains' => ['sometimes', 'array', 'min:1'], 'domains.*' => ['string', 'max:253'], 'issuer' => ['sometimes', 'string', 'max:160'], 'expires_at' => ['sometimes', 'date', 'after:now'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => self::resource($update->execute($certificate, $data))]);
    }

    public function store(Request $request, IssueCertificate $issue): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['domains' => ['required', 'array', 'min:1'], 'domains.*' => ['string', 'max:253'], 'issuer' => ['nullable', 'string', 'max:100'], 'expires_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);
        $certificate = $issue->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => self::resource($certificate)], 201);
    }

    public function acme(Request $request, RegisterAcmeAccount $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['email' => ['required', 'email'], 'directory' => ['nullable', 'url'], 'credentials' => ['nullable', 'array']]);
        $account = $register->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $account->getKey(), 'type' => 'control-panel-acme-account', 'attributes' => $account->only(['email', 'directory', 'active'])]], 201);
    }

    public function operation(Request $request, RecordCertificateOperation $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['certificate_id' => ['nullable', 'uuid'], 'operation' => ['required', 'in:deploy,renew,revoke,expiry-check'], 'status' => ['nullable', 'in:queued,running,completed,failed'], 'details' => ['nullable', 'array']]);
        $item = $record->execute(array_merge($data, ['team_id' => $teamId]));

        return response()->json(['data' => ['id' => $item->getKey(), 'type' => 'control-panel-certificate-operation', 'attributes' => $item->only(['certificate_id', 'operation', 'status', 'details'])]], 201);
    }

    public function lifecycle(Request $request, RegisterCertificateLifecycle $register): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['kind' => ['required', 'in:deployment,renewal,expiry'], 'payload' => ['required', 'array']]);
        $item = $register->execute(array_merge($data['payload'], ['kind' => $data['kind'], 'team_id' => $teamId]));

        return response()->json(['data' => self::lifecycleResource($item, 'control-panel-certificate-'.$data['kind'], $data['kind'])], 201);
    }

    public function deploy(Request $request, string $certificate, RequestCertificateDeployment $deploy): JsonResponse
    {
        $item = $this->findForTeam($request, $certificate);
        $data = $request->validate([
            'target_type' => ['required', 'string', 'max:120'],
            'target_id' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $deployment = $deploy->execute($item, $data);

        return response()->json(['data' => self::lifecycleResource($deployment, 'control-panel-certificate-deployment', 'deployment')], 202);
    }

    public function renew(Request $request, string $certificate, RequestCertificateRenewal $renew): JsonResponse
    {
        $item = $this->findForTeam($request, $certificate);
        $data = $request->validate(['scheduled_at' => ['nullable', 'date']]);
        $scheduledAt = isset($data['scheduled_at']) ? new \DateTimeImmutable($data['scheduled_at']) : null;

        $renewal = $renew->execute($item, $scheduledAt);

        return response()->json(['data' => self::lifecycleResource($renewal, 'control-panel-certificate-renewal', 'renewal')], 202);
    }

    public function expiryCheck(Request $request, string $certificate, CheckCertificateExpiry $check): JsonResponse
    {
        $item = $this->findForTeam($request, $certificate);
        $data = $request->validate(['threshold_days' => ['nullable', 'integer', 'between:1,365']]);

        $alert = $check->execute($item, (int) ($data['threshold_days'] ?? 30));

        return response()->json(['data' => self::lifecycleResource($alert, 'control-panel-certificate-expiry', 'expiry')]);
    }

    public function expire(Request $request, string $certificate, ExpireCertificate $expire): JsonResponse
    {
        $item = $this->findForTeam($request, $certificate);

        return response()->json(['data' => self::resource($expire->execute($item))]);
    }

    public function revoke(Request $request, string $certificate, RevokeCertificate $revoke): JsonResponse
    {
        $item = $this->findForTeam($request, $certificate);

        return response()->json(['data' => self::resource($revoke->execute($item))]);
    }

    private static function resource(Certificate $certificate): array
    {
        return ['id' => $certificate->getKey(), 'type' => 'control-panel-certificate', 'attributes' => $certificate->only(['domains', 'status', 'issuer', 'issued_at', 'expires_at', 'metadata'])];
    }

    private static function lifecycleResource(Model $item, string $type, string $kind): array
    {
        return ['id' => $item->getKey(), 'type' => $type, ...$item->only(self::LIFECYCLE_FIELDS[$kind])];
    }

    private function findForTeam(Request $request, string $id): Certificate
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return Certificate::query()->whereKey($id)->where('team_id', $teamId)->firstOrFail();
    }
}
