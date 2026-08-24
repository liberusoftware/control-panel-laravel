<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Enums;

enum ZoneStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
