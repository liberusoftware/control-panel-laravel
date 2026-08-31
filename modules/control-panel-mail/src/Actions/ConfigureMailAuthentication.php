<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\MailDomain;

final class ConfigureMailAuthentication
{
    public function __construct(private readonly RotateDkimKey $rotateDkimKey) {}

    /** @param array<string, mixed> $attributes */
    public function execute(MailDomain $domain, array $attributes = []): MailDomain
    {
        $policy = (string) ($attributes['dmarc_policy'] ?? 'none');
        $percentage = (int) ($attributes['dmarc_percentage'] ?? 100);
        $selector = (string) ($attributes['dkim_selector'] ?? 'default');
        $rua = (string) ($attributes['dmarc_rua_email'] ?? 'postmaster@'.$domain->domain);
        $ruf = (string) ($attributes['dmarc_ruf_email'] ?? 'postmaster@'.$domain->domain);

        if (! in_array($policy, ['none', 'quarantine', 'reject'], true)) {
            throw ValidationException::withMessages(['dmarc_policy' => 'The DMARC policy is invalid.']);
        }
        if ($percentage < 0 || $percentage > 100) {
            throw ValidationException::withMessages(['dmarc_percentage' => 'The DMARC percentage must be between 0 and 100.']);
        }
        if (filter_var($rua, FILTER_VALIDATE_EMAIL) === false || filter_var($ruf, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['dmarc_rua_email' => 'Valid DMARC report addresses are required.']);
        }

        $key = $this->rotateDkimKey->execute((string) $domain->team_id, $domain->domain, $selector);
        $publicKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r"], '', $key->public_key);
        $spfRecord = (string) ($attributes['spf_record'] ?? 'v=spf1 mx a ~all');
        $dmarcRecord = implode('; ', [
            'v=DMARC1',
            'p='.$policy,
            'pct='.$percentage,
            'rua=mailto:'.$rua,
            'ruf=mailto:'.$ruf,
            'fo=1',
            'adkim=r',
            'aspf=r',
        ]);

        $domain->forceFill([
            'dkim' => ['enabled' => (bool) ($attributes['dkim_enabled'] ?? true), 'selector' => $selector, 'dns_record' => 'v=DKIM1; k=rsa; p='.$publicKey],
            'spf' => ['enabled' => (bool) ($attributes['spf_enabled'] ?? true), 'record' => $spfRecord],
            'dmarc' => ['enabled' => (bool) ($attributes['dmarc_enabled'] ?? true), 'policy' => $policy, 'percentage' => $percentage, 'rua' => $rua, 'ruf' => $ruf, 'record' => $dmarcRecord],
        ])->save();

        return $domain->refresh();
    }
}
