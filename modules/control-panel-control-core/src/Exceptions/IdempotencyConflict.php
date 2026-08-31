<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCore\Exceptions;

use RuntimeException;

final class IdempotencyConflict extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The idempotency key was already used for a different request.');
    }
}
