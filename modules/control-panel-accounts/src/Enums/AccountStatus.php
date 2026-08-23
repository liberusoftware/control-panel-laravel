<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
