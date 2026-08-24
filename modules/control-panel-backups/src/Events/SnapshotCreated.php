<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;

final readonly class SnapshotCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public BackupSnapshot $snapshot) {}
}
