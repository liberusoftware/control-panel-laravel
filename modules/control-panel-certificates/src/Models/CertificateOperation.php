<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Certificates\Models;
use Illuminate\Database\Eloquent\Model;
final class CertificateOperation extends Model
{
    protected $table = 'control_panel_certificate_operations';
    protected $fillable = ['id','team_id','certificate_id','operation','status','details','completed_at'];
    protected function casts(): array { return ['details' => 'array', 'completed_at' => 'datetime']; }
}
