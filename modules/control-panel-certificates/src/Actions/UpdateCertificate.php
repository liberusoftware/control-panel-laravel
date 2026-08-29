<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Models\Certificate;

final class UpdateCertificate
{
    /** @param array<string, mixed> $attributes */
    public function execute(Certificate $certificate, array $attributes): Certificate
    {
        if (in_array($certificate->status->value, ['revoked', 'expired'], true)) {
            throw ValidationException::withMessages(['certificate' => 'A terminal certificate cannot be updated.']);
        }

        $domains = array_values(array_filter(array_map(static fn (mixed $domain): string => strtolower(trim((string) $domain)), (array) ($attributes['domains'] ?? $certificate->domains))));
        $issuer = trim((string) ($attributes['issuer'] ?? $certificate->issuer));
        $expiresAt = Carbon::parse($attributes['expires_at'] ?? $certificate->expires_at);
        if ($domains === [] || $issuer === '' || $expiresAt->isPast()) {
            throw ValidationException::withMessages(['certificate' => 'At least one domain, an issuer, and a future expiry are required.']);
        }

        $certificate->forceFill(['domains' => $domains, 'issuer' => $issuer, 'expires_at' => $expiresAt, 'metadata' => $attributes['metadata'] ?? $certificate->metadata])->save();

        return $certificate->refresh();
    }
}
