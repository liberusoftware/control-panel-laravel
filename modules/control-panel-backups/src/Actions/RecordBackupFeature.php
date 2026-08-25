<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Backups\Models\BackupEncryption;
use Liberu\ControlPanel\Backups\Models\BackupExecution;
use Liberu\ControlPanel\Backups\Models\OffsiteTransfer;

final class RecordBackupFeature
{
    public function execute(array $a): Model
    {
        $kind = (string) ($a['kind'] ?? '');
        $map = ['execution' => BackupExecution::class, 'encryption' => BackupEncryption::class, 'offsite' => OffsiteTransfer::class];
        if (! isset($map[$kind])) {
            throw ValidationException::withMessages(['kind' => 'Unsupported backup feature.']);
        } $a['id'] = $a['id'] ?? (string) Str::uuid();
        $a['team_id'] = $a['team_id'] ?? null;
        if ($kind === 'execution') {
            $a['status'] = $a['status'] ?? 'queued';
        } if ($kind === 'offsite') {
            $a['status'] = $a['status'] ?? 'queued';
        } unset($a['kind']);

        return $map[$kind]::query()->create($a);
    }
}
