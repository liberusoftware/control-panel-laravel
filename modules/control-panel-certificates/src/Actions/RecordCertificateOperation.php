<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Models\CertificateOperation;

final class RecordCertificateOperation
{
    public function execute(array $attributes): CertificateOperation
    {
        $operation = trim((string) ($attributes['operation'] ?? ''));
        if (! in_array($operation, ['deploy', 'renew', 'revoke', 'expiry-check'], true)) {
            throw ValidationException::withMessages(['operation' => 'Unsupported certificate operation.']);
        }

        return CertificateOperation::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'certificate_id' => $attributes['certificate_id'] ?? null, 'operation' => $operation, 'status' => $attributes['status'] ?? 'queued', 'details' => $attributes['details'] ?? []]);
    }
}
