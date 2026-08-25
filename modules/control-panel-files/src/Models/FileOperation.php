<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Files\Models;
use Illuminate\Database\Eloquent\Model;
final class FileOperation extends Model { protected $table='control_panel_file_operations'; protected $fillable=['id','team_id','file_id','operation','status','details']; protected function casts():array{return ['details'=>'array'];} }
