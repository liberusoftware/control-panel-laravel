<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Accounts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class HostingPackage extends Model
{
    use HasUuids;

    protected $table = 'control_panel_hosting_packages';

    protected $fillable = ['team_id', 'name', 'limits', 'features', 'active'];

    protected function casts(): array
    {
        return ['limits' => 'array', 'features' => 'array', 'active' => 'bool'];
    }
}
