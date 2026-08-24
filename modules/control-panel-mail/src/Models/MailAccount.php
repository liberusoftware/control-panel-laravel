<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MailAccount extends Model
{
    use HasUuids;

    protected $table = 'control_panel_mail_accounts';

    protected $fillable = ['team_id', 'domain', 'address', 'status', 'quota_bytes', 'settings'];

    protected $hidden = ['settings'];

    protected function casts(): array
    {
        return ['quota_bytes' => 'integer', 'settings' => 'encrypted:array'];
    }
}
