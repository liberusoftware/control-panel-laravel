<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Containers\Models;
use Illuminate\Database\Eloquent\Model;
final class ContainerVolume extends Model
{
    protected $table = 'control_panel_container_volumes';
    protected $fillable = ['id','team_id','name','driver','mount_path','size_bytes','status','metadata'];
    protected function casts(): array { return ['size_bytes'=>'integer','metadata'=>'array']; }
}
