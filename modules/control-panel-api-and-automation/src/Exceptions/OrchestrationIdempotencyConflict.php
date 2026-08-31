<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomation\Exceptions;

use RuntimeException;

final class OrchestrationIdempotencyConflict extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The idempotency key was already used for a different orchestration request.');
    }
}
