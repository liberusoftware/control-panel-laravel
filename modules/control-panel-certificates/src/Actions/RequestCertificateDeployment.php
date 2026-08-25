<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Models\CertificateDeployment;

final class RequestCertificateDeployment
{
    /** @param array<string, mixed> $attributes */
    public function execute(Certificate $certificate, array $attributes): CertificateDeployment
    {
        if ($certificate->status !== CertificateStatus::Active) {
            throw ValidationException::withMessages(['certificate' => 'Only active certificates can be deployed.']);
        }

        $targetType = trim((string) ($attributes['target_type'] ?? ''));
        $targetId = trim((string) ($attributes['target_id'] ?? ''));

        if ($targetType === '' || $targetId === '') {
            throw ValidationException::withMessages(['target' => 'A deployment target type and identifier are required.']);
        }

        return CertificateDeployment::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $certificate->team_id,
            'certificate_id' => $certificate->getKey(),
            'target_type' => $targetType,
            'target_id' => $targetId,
            'status' => 'queued',
            'metadata' => $attributes['metadata'] ?? [],
        ]);
    }
}
