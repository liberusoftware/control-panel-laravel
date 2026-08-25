<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Models\CertificateRenewal;

final class RequestCertificateRenewal
{
    public function execute(Certificate $certificate, ?\DateTimeInterface $scheduledAt = null): CertificateRenewal
    {
        if ($certificate->status === CertificateStatus::Revoked) {
            throw ValidationException::withMessages(['certificate' => 'Revoked certificates cannot be renewed.']);
        }

        $existing = CertificateRenewal::query()
            ->where('certificate_id', $certificate->getKey())
            ->whereIn('status', ['queued', 'running'])
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages(['certificate' => 'A renewal is already queued or running.']);
        }

        return CertificateRenewal::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $certificate->team_id,
            'certificate_id' => $certificate->getKey(),
            'scheduled_at' => $scheduledAt ?? now(),
            'status' => 'queued',
            'attempts' => 0,
        ]);
    }
}
