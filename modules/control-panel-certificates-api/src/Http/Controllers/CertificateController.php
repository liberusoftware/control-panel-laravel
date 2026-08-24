<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\Actions\RecordCertificateOperation;
use Liberu\ControlPanel\Certificates\Actions\RegisterAcmeAccount;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Queries\ListCertificates;

final class CertificateController
{
    public function index(Request $request, ListCertificates $list): JsonResponse
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $certificates = $list->execute($teamId, $request->integer('per_page', 25));

        return response()->json(['data' => $certificates->through(static fn (Certificate $certificate): array => self::resource($certificate)), 'meta' => ['current_page' => $certificates->currentPage(), 'per_page' => $certificates->perPage(), 'total' => $certificates->total()]]);
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
        $teamId = $request->user()?->current_team_id; abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['email'=>['required','email'],'directory'=>['nullable','url'],'credentials'=>['nullable','array']]);
        $account = $register->execute(array_merge($data, ['team_id'=>$teamId]));
        return response()->json(['data'=>['id'=>$account->getKey(),'type'=>'control-panel-acme-account','attributes'=>$account->only(['email','directory','active'])]], 201);
    }

    public function operation(Request $request, RecordCertificateOperation $record): JsonResponse
    {
        $teamId = $request->user()?->current_team_id; abort_if($teamId === null, 403, 'A current team is required.');
        $data = $request->validate(['certificate_id'=>['nullable','uuid'],'operation'=>['required','in:deploy,renew,revoke,expiry-check'],'status'=>['nullable','in:queued,running,completed,failed'],'details'=>['nullable','array']]);
        $item = $record->execute(array_merge($data, ['team_id'=>$teamId]));
        return response()->json(['data'=>['id'=>$item->getKey(),'type'=>'control-panel-certificate-operation','attributes'=>$item->only(['certificate_id','operation','status','details'])]], 201);
    }

    private static function resource(Certificate $certificate): array
    {
        return ['id' => $certificate->getKey(), 'type' => 'control-panel-certificate', 'attributes' => $certificate->only(['domains', 'status', 'issuer', 'issued_at', 'expires_at', 'metadata'])];
    }
}
