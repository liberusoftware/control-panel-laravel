<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Actions;

use Illuminate\Validation\ValidationException;
use phpseclib3\Crypt\RSA;

final class GenerateSshKeyPair
{
    /** @return array{public_key: string, private_key: string} */
    public function execute(?string $passphrase = null, int $bits = 4096, ?string $comment = null): array
    {
        if (! in_array($bits, [2048, 4096], true)) {
            throw ValidationException::withMessages(['bits' => 'RSA keys must use 2048 or 4096 bits.']);
        }

        if ($passphrase !== null && $passphrase !== '' && mb_strlen($passphrase) < 8) {
            throw ValidationException::withMessages(['passphrase' => 'The passphrase must contain at least 8 characters.']);
        }

        if ($comment !== null && mb_strlen($comment) > 255) {
            throw ValidationException::withMessages(['comment' => 'The comment may not exceed 255 characters.']);
        }

        $key = RSA::createKey($bits);
        $publicKey = $key->getPublicKey()->toString('OpenSSH', array_filter(['comment' => $comment]));

        if ($passphrase !== null && $passphrase !== '') {
            $key = $key->withPassword($passphrase);
        }

        return [
            'public_key' => $publicKey,
            'private_key' => $key->toString('PKCS8'),
        ];
    }
}
