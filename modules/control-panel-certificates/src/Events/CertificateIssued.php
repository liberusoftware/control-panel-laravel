<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Liberu\ControlPanel\Certificates\Models\Certificate;

final readonly class CertificateIssued implements ShouldDispatchAfterCommit
{
    public function __construct(public Certificate $certificate) {}
}
