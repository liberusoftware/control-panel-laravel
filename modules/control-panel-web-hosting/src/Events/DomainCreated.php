<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class DomainCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public string $domainId, public string $hostname) {}
}
