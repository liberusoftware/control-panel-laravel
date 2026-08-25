<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Actions;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Mail\Models\DkimKey;

final class RotateDkimKey
{
    public function execute(string $teamId, string $domain, string $selector = 'default'): DkimKey
    {
        $domain = strtolower(trim($domain));
        $selector = strtolower(trim($selector));

        if (! filter_var('postmaster@'.$domain, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['domain' => 'A valid mail domain is required.']);
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $selector)) {
            throw ValidationException::withMessages(['selector' => 'The DKIM selector contains invalid characters.']);
        }

        $privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($privateKey === false || ! openssl_pkey_export($privateKey, $privateKeyPem)) {
            throw ValidationException::withMessages(['key' => 'A DKIM key could not be generated.']);
        }

        $details = openssl_pkey_get_details($privateKey);
        $publicKeyPem = is_array($details) ? ($details['key'] ?? null) : null;
        if (! is_string($publicKeyPem) || $publicKeyPem === '') {
            throw ValidationException::withMessages(['key' => 'A DKIM public key could not be generated.']);
        }

        DkimKey::query()
            ->where('team_id', $teamId)
            ->where('domain', $domain)
            ->where('selector', $selector)
            ->update(['active' => false]);

        return DkimKey::query()->create([
            'id' => (string) Str::uuid(),
            'team_id' => $teamId,
            'domain' => $domain,
            'selector' => $selector,
            'public_key' => $publicKeyPem,
            'private_key' => $privateKeyPem,
            'active' => true,
            'rotated_at' => now(),
        ]);
    }
}
