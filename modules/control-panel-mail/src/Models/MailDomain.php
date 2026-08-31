<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MailDomain extends Model
{
    use HasUuids;

    protected $table = 'control_panel_mail_domains';

    protected $fillable = ['id', 'team_id', 'domain', 'status', 'dkim', 'spf', 'dmarc'];

    protected function casts(): array
    {
        return ['dkim' => 'array', 'spf' => 'array', 'dmarc' => 'array'];
    }
}
