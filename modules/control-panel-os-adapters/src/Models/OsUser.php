<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OsUser extends Model
{
    use HasUuids;
    protected $table = 'control_panel_os_users';
    protected $fillable = ['team_id', 'node_id', 'username', 'uid', 'shell', 'home', 'sudo', 'status', 'metadata'];
    protected function casts(): array { return ['uid' => 'integer', 'sudo' => 'bool', 'metadata' => 'array']; }
}
