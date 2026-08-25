<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Model;

final class MailOperation extends Model
{
    protected $table = 'control_panel_mail_operations';

    protected $fillable = ['id', 'team_id', 'mail_account_id', 'operation', 'status', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }
}
