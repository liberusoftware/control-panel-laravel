<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class DkimKey extends Model
{
    use HasUuids;

    protected $table = 'control_panel_mail_dkim_keys';

    protected $fillable = ['id', 'team_id', 'domain', 'selector', 'public_key', 'private_key', 'active', 'rotated_at'];

    protected $hidden = ['private_key'];

    protected function casts(): array
    {
        return ['private_key' => 'encrypted', 'active' => 'boolean', 'rotated_at' => 'datetime'];
    }
}
