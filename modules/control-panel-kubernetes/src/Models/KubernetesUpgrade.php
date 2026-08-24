<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Kubernetes\Models;
use Illuminate\Database\Eloquent\Model;
final class KubernetesUpgrade extends Model { protected $table='control_panel_kubernetes_upgrades'; protected $fillable=['id','team_id','cluster_id','from_version','to_version','status','started_at','completed_at','details']; protected function casts():array{return ['started_at'=>'datetime','completed_at'=>'datetime','details'=>'array'];} }
