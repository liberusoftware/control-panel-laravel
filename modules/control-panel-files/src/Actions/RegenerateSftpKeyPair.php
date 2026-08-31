<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Models\SftpAccount;
use phpseclib3\Crypt\RSA;

final class RegenerateSftpKeyPair
{
    /** @return array{public_key: string, private_key: string} */
    public function execute(SftpAccount $account, ?int $bits = null): array
    {
        $bits ??= $account->ssh_key_bits ?: 4096;
        if (! in_array($bits, [2048, 4096], true)) {
            throw ValidationException::withMessages(['ssh_key_bits' => 'RSA keys must use 2048 or 4096 bits.']);
        }

        $key = RSA::createKey($bits);
        $publicKey = $key->getPublicKey()->toString('OpenSSH', ['comment' => 'control-panel-sftp']);
        $privateKey = $key->toString('PKCS8');

        $account->forceFill([
            'ssh_public_key' => $publicKey,
            'ssh_private_key' => $privateKey,
            'ssh_key_auth_enabled' => true,
            'ssh_key_type' => 'rsa',
            'ssh_key_bits' => $bits,
        ])->save();

        return ['public_key' => $publicKey, 'private_key' => $privateKey];
    }
}
