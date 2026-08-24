<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Databases\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Liberu\ControlPanel\Databases\Models\Database;

final readonly class DatabaseCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public Database $database) {}
}
