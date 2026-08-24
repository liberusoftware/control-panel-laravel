<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Containers\Models;
use Illuminate\Database\Eloquent\Model;
final class ContainerRegistry extends Model
{
    protected $table = 'control_panel_container_registries';
    protected $fillable = ['id','team_id','name','endpoint','username','credential','tls_verify','active'];
    protected $hidden = ['credential'];
    protected function casts(): array { return ['credential'=>'encrypted','tls_verify'=>'boolean','active'=>'boolean']; }
}
