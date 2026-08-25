<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\Certificates\Models\Certificate;

final class ExpireCertificate
{
    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->status !== CertificateStatus::Active) {
            throw ValidationException::withMessages(['certificate' => 'Only active certificates can expire.']);
        }
        if ($certificate->expires_at === null || Carbon::parse($certificate->expires_at)->isFuture()) {
            throw ValidationException::withMessages(['expires_at' => 'The certificate expiration time must be in the past.']);
        }

        return DB::transaction(function () use ($certificate): Certificate {
            $certificate->forceFill(['status' => CertificateStatus::Expired])->save();

            return $certificate->refresh();
        });
    }
}
