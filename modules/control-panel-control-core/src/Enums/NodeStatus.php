<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Enums;

enum NodeStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Draining = 'draining';
    case Suspended = 'suspended';
    case Decommissioned = 'decommissioned';
}
