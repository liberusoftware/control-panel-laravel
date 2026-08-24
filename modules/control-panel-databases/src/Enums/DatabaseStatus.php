<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Enums;

enum DatabaseStatus: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
