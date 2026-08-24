<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Enums;

enum DomainStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
