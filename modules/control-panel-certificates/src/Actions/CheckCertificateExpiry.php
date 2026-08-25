<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Models\CertificateExpiryAlert;

final class CheckCertificateExpiry
{
    public function execute(Certificate $certificate, int $thresholdDays = 30): CertificateExpiryAlert
    {
        if ($thresholdDays < 1 || $thresholdDays > 365) {
            throw ValidationException::withMessages(['threshold_days' => 'The expiry threshold must be between 1 and 365 days.']);
        }

        $expiresAt = $certificate->expires_at;
        $isExpiring = $expiresAt !== null && $expiresAt->lessThanOrEqualTo(now()->addDays($thresholdDays));

        $alert = CertificateExpiryAlert::query()->firstOrNew([
            'team_id' => $certificate->team_id,
            'certificate_id' => $certificate->getKey(),
            'threshold_days' => $thresholdDays,
        ]);

        $alert->fill([
            'status' => $isExpiring ? 'triggered' : 'clear',
            'notified_at' => $isExpiring ? now() : null,
            'metadata' => ['expires_at' => $expiresAt?->toISOString()],
        ]);

        if (! $alert->exists) {
            $alert->id = (string) Str::uuid();
        }

        $alert->save();

        return $alert;
    }
}
