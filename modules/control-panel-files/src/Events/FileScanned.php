<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Liberu\ControlPanel\Files\Models\FileEntry;

final readonly class FileScanned implements ShouldDispatchAfterCommit
{
    public function __construct(public FileEntry $file) {}
}
