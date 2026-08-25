<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\ControlPanel\WebHosting\Enums\CertificateStatus;

final class SslCertificate extends Model
{
    use HasUuids;

    protected $table = 'control_panel_ssl_certificates';

    protected $fillable = ['team_id', 'domain_id', 'issuer', 'serial', 'status', 'issued_at', 'expires_at', 'auto_renew', 'metadata'];

    protected $hidden = ['serial'];

    protected function casts(): array
    {
        return ['status' => CertificateStatus::class, 'issued_at' => 'datetime', 'expires_at' => 'datetime', 'auto_renew' => 'bool', 'metadata' => 'array'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
