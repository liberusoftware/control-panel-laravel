<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Enums;

enum CertificateStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Revoked = 'revoked';
    case Expired = 'expired';
}
