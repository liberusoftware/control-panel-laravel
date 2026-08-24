<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Containers\Models;
use Illuminate\Database\Eloquent\Model;
final class ContainerNetwork extends Model
{
    protected $table = 'control_panel_container_networks';
    protected $fillable = ['id','team_id','name','driver','subnet','gateway','options','status'];
    protected function casts(): array { return ['options'=>'array']; }
}
