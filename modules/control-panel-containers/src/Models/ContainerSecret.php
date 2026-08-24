<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Containers\Models;
use Illuminate\Database\Eloquent\Model;
final class ContainerSecret extends Model
{
    protected $table = 'control_panel_container_secrets';
    protected $fillable = ['id','team_id','name','value','metadata','active'];
    protected $hidden = ['value'];
    protected function casts(): array { return ['value'=>'encrypted','metadata'=>'array','active'=>'boolean']; }
}
