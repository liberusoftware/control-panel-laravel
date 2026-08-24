<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OsPackage extends Model
{
    use HasUuids;
    protected $table = 'control_panel_os_packages';
    protected $fillable = ['team_id', 'node_id', 'name', 'version', 'architecture', 'status', 'metadata'];
    protected function casts(): array { return ['metadata' => 'array']; }
}
