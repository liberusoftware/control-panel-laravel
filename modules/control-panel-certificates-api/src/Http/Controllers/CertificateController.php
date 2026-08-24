<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\Certificates\Actions\IssueCertificate;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Queries\ListCertificates;

final class CertificateController
{
    public function index(Request $request, ListCertificates $list): JsonResponse
    {
        $certificates = $list->execute($request->user()?->current_team_id, $request->integer('per_page', 25));

        return response()->json(['data' => $certificates->through(static fn (Certificate $certificate): array => self::resource($certificate)), 'meta' => ['current_page' => $certificates->currentPage(), 'per_page' => $certificates->perPage(), 'total' => $certificates->total()]]);
    }

    public function store(Request $request, IssueCertificate $issue): JsonResponse
    {
        $data = $request->validate(['domains' => ['required', 'array', 'min:1'], 'domains.*' => ['string', 'max:253'], 'issuer' => ['nullable', 'string', 'max:100'], 'expires_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);
        $certificate = $issue->execute(array_merge($data, ['team_id' => $request->user()?->current_team_id]));

        return response()->json(['data' => self::resource($certificate)], 201);
    }

    private static function resource(Certificate $certificate): array
    {
        return ['id' => $certificate->getKey(), 'type' => 'control-panel-certificate', 'attributes' => $certificate->only(['domains', 'status', 'issuer', 'issued_at', 'expires_at', 'metadata'])];
    }
}
