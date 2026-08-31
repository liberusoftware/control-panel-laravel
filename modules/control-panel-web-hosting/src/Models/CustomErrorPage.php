<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomErrorPage extends Model
{
    use HasUuids;

    protected $table = 'control_panel_custom_error_pages';

    protected $fillable = ['team_id', 'domain_id', 'error_code', 'custom_content', 'custom_file_path', 'active'];

    protected function casts(): array
    {
        return ['error_code' => 'integer', 'active' => 'bool'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /** @return array<int, string> */
    public static function commonErrorCodes(): array
    {
        return [400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 408 => 'Request Timeout', 410 => 'Gone', 429 => 'Too Many Requests', 500 => 'Internal Server Error', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout'];
    }
}
