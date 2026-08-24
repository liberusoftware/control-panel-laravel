<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Liberu\ControlPanel\Files\Enums\FileStatus;
use Liberu\ControlPanel\Files\Events\FileScanned;
use Liberu\ControlPanel\Files\Models\FileEntry;

final readonly class MarkFileScanned
{
    public function __construct(private Dispatcher $events) {}

    public function execute(FileEntry $file, bool $clean): FileEntry
    {
        return DB::transaction(function () use ($file, $clean): FileEntry {
            $file->update(['status' => $clean ? FileStatus::Available : FileStatus::Quarantined, 'scanned_at' => now()]);
            $file = $file->refresh();
            $this->events->dispatch(new FileScanned($file));

            return $file;
        });
    }
}
