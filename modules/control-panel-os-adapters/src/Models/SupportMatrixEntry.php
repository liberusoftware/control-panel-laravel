<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class SupportMatrixEntry extends Model
{
    use HasUuids;
    protected $table = 'control_panel_os_support_matrix';
    protected $fillable = ['operating_system', 'version', 'capability', 'supported', 'minimum_adapter_version', 'notes'];
    protected function casts(): array { return ['supported' => 'bool']; }
}
