<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Backups\Models;
use Illuminate\Database\Eloquent\Model;
final class BackupEncryption extends Model { protected $table='control_panel_backup_encryptions'; protected $fillable=['id','team_id','policy_id','algorithm','key_reference','active','rotated_at']; protected function casts():array{return ['key_reference'=>'encrypted','active'=>'boolean','rotated_at'=>'datetime'];} }
