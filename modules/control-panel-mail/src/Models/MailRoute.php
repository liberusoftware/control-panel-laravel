<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MailRoute extends Model
{
    use HasUuids;

    protected $table = 'control_panel_mail_routes';

    protected $fillable = ['id', 'team_id', 'domain', 'source_pattern', 'destination', 'priority', 'active'];

    protected function casts(): array
    {
        return ['priority' => 'integer', 'active' => 'boolean'];
    }
}
