<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Enums;

enum CredentialStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';
}
