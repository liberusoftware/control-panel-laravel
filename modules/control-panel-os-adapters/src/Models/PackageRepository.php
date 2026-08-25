<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdapters\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class PackageRepository extends Model
{
    use HasUuids;
    protected $table = 'control_panel_package_repositories';
    protected $fillable = ['team_id', 'node_id', 'name', 'url', 'distribution', 'enabled', 'trusted', 'metadata'];
    protected $attributes = ['enabled' => true, 'trusted' => false];
    protected function casts(): array { return ['enabled' => 'bool', 'trusted' => 'bool', 'metadata' => 'array']; }
}
