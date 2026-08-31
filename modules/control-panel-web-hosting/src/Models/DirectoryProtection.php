<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DirectoryProtection extends Model
{
    use HasUuids;

    protected $table = 'control_panel_directory_protections';

    protected $fillable = ['team_id', 'domain_id', 'directory_path', 'auth_name', 'htpasswd_file_path', 'active'];

    protected function casts(): array
    {
        return ['active' => 'bool'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(DirectoryProtectionUser::class);
    }
}
