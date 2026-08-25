<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Backups\Models;

use Illuminate\Database\Eloquent\Model;

final class OffsiteTransfer extends Model
{
    protected $table = 'control_panel_backup_offsite_transfers';

    protected $fillable = ['id', 'team_id', 'snapshot_id', 'destination_id', 'status', 'attempts', 'transferred_at', 'error', 'metadata'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'transferred_at' => 'datetime', 'metadata' => 'array'];
    }
}
