<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MailAlias extends Model
{
    use HasUuids;

    protected $table = 'control_panel_mail_aliases';

    protected $fillable = ['id', 'team_id', 'domain', 'address', 'destinations', 'active'];

    protected function casts(): array
    {
        return ['destinations' => 'array', 'active' => 'boolean'];
    }
}
