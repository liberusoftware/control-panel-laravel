<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Illuminate\Support\Str;
use Liberu\ControlPanel\WebHosting\Enums\CertificateStatus;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\SslCertificate;

final class RequestCertificate
{
    /** @param array<string, mixed> $attributes */
    public function execute(Domain $domain, array $attributes = []): SslCertificate
    {
        return SslCertificate::query()->create(['id' => (string) Str::uuid(), 'team_id' => $domain->team_id, 'domain_id' => $domain->getKey(), 'issuer' => $attributes['issuer'] ?? 'acme', 'status' => CertificateStatus::Pending, 'auto_renew' => (bool) ($attributes['auto_renew'] ?? true), 'metadata' => $attributes['metadata'] ?? []]);
    }
}
