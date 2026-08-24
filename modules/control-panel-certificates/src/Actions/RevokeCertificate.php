<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\Certificates\Events\CertificateRevoked;
use Liberu\ControlPanel\Certificates\Models\Certificate;

final readonly class RevokeCertificate
{
    public function __construct(private Dispatcher $events) {}

    public function execute(Certificate $certificate): Certificate
    {
        if ($certificate->status === CertificateStatus::Revoked) {
            throw ValidationException::withMessages(['certificate' => 'The certificate is already revoked.']);
        }

        return DB::transaction(function () use ($certificate): Certificate {
            $certificate->update(['status' => CertificateStatus::Revoked]);
            $certificate = $certificate->refresh();
            $this->events->dispatch(new CertificateRevoked($certificate));

            return $certificate;
        });
    }
}
