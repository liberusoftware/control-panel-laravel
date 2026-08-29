<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Files\Enums\FileStatus;
use Liberu\ControlPanel\Files\Models\FileEntry;

final class DeleteFile
{
    public function execute(FileEntry $file): FileEntry
    {
        if ($file->status === FileStatus::Retained) {
            throw ValidationException::withMessages(['file' => 'A retained file cannot be deleted.']);
        }

        $file->update(['status' => FileStatus::Deleted]);

        return $file->refresh();
    }
}
