<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Enums;

enum AccountType: string
{
    case Customer = 'customer';
    case Reseller = 'reseller';
    case Administrator = 'administrator';
}
