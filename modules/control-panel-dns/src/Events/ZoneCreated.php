<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Dns\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Liberu\ControlPanel\Dns\Models\Zone;

final readonly class ZoneCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public Zone $zone) {}
}
