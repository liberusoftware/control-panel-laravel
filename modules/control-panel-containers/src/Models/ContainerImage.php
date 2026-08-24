<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Containers\Models;
use Illuminate\Database\Eloquent\Model;
final class ContainerImage extends Model
{
    protected $table = 'control_panel_container_images';
    protected $fillable = ['id','team_id','repository','tag','digest','size_bytes','architecture','status','metadata'];
    protected function casts(): array { return ['size_bytes'=>'integer','metadata'=>'array']; }
}
