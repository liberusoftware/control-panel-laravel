<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Enums\CertificateStatus;
use Liberu\ControlPanel\Certificates\Events\CertificateIssued;
use Liberu\ControlPanel\Certificates\Models\Certificate;

final readonly class IssueCertificate
{
    public function __construct(private Dispatcher $events) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): Certificate
    {
        $domains = array_values(array_filter(array_map(static fn (mixed $domain): string => strtolower(trim((string) $domain)), (array) ($attributes['domains'] ?? []))));
        if ($domains === []) {
            throw ValidationException::withMessages(['domains' => 'At least one certificate domain is required.']);
        }

        return DB::transaction(function () use ($attributes, $domains): Certificate {
            $certificate = Certificate::query()->create(['id' => (string) Str::uuid(), 'team_id' => $attributes['team_id'] ?? null, 'domains' => $domains, 'status' => CertificateStatus::Active, 'issuer' => $attributes['issuer'] ?? 'acme', 'certificate_pem' => $attributes['certificate_pem'] ?? null, 'private_key' => $attributes['private_key'] ?? null, 'issued_at' => now(), 'expires_at' => $attributes['expires_at'] ?? now()->addDays(90), 'metadata' => $attributes['metadata'] ?? []]);
            $this->events->dispatch(new CertificateIssued($certificate));

            return $certificate;
        });
    }
}
