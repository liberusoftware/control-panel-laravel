<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MimeType extends Model
{
    use HasUuids;

    protected $table = 'control_panel_mime_types';

    protected $fillable = ['team_id', 'domain_id', 'extension', 'mime_type', 'active'];

    protected function casts(): array
    {
        return ['active' => 'bool'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /** @return array<string, string> */
    public static function commonMimeTypes(): array
    {
        return [
            '.webp' => 'image/webp', '.svg' => 'image/svg+xml', '.woff' => 'font/woff',
            '.woff2' => 'font/woff2', '.ttf' => 'font/ttf', '.otf' => 'font/otf',
            '.mp4' => 'video/mp4', '.webm' => 'video/webm', '.ogg' => 'audio/ogg',
            '.mp3' => 'audio/mpeg', '.wav' => 'audio/wav', '.json' => 'application/json',
            '.xml' => 'application/xml',
        ];
    }
}
