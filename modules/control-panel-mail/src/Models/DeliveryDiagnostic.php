<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail\Models;

use Illuminate\Database\Eloquent\Model;

final class DeliveryDiagnostic extends Model
{
    protected $table = 'control_panel_mail_delivery_diagnostics';

    protected $fillable = ['id', 'team_id', 'mail_account_id', 'message_id', 'recipient', 'status', 'response', 'checked_at'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }
}
