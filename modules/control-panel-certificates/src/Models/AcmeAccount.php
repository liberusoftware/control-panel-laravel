<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Certificates\Models;

use Illuminate\Database\Eloquent\Model;

final class AcmeAccount extends Model
{
    protected $table = 'control_panel_certificate_acme_accounts';

    protected $fillable = ['id', 'team_id', 'email', 'directory', 'credentials', 'active'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'active' => 'boolean'];
    }
}
