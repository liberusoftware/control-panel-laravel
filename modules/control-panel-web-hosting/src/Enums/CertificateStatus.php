<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Enums;

enum CertificateStatus: string
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
