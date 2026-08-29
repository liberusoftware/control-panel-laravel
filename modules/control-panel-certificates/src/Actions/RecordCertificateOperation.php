<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Models\CertificateOperation;

final class RecordCertificateOperation
{
    public function execute(array $attributes): CertificateOperation
    {
        $teamId = trim((string) ($attributes['team_id'] ?? ''));
        if ($teamId === '') {
            throw ValidationException::withMessages(['team_id' => 'A team is required.']);
        }

        $operation = trim((string) ($attributes['operation'] ?? ''));
        if (! in_array($operation, ['deploy', 'renew', 'revoke', 'expiry-check'], true)) {
            throw ValidationException::withMessages(['operation' => 'Unsupported certificate operation.']);
        }

        $certificateId = $attributes['certificate_id'] ?? null;
        if ($certificateId !== null && ! Certificate::query()->whereKey($certificateId)->where('team_id', $teamId)->exists()) {
            abort(404);
        }

        return CertificateOperation::query()->create(['id' => (string) Str::uuid(), 'team_id' => $teamId, 'certificate_id' => $certificateId, 'operation' => $operation, 'status' => $attributes['status'] ?? 'queued', 'details' => $attributes['details'] ?? []]);
    }
}
