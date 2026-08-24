<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Kubernetes\Models;
use Illuminate\Database\Eloquent\Model;
final class KubernetesNode extends Model { protected $table='control_panel_kubernetes_nodes'; protected $fillable=['id','team_id','cluster_id','name','uid','kubernetes_version','container_runtime','os_image','architecture','status','schedulable','labels','annotations','taints','addresses','capacity','allocatable','conditions','last_heartbeat_at']; protected function casts():array{return ['schedulable'=>'boolean','labels'=>'array','annotations'=>'array','taints'=>'array','addresses'=>'array','capacity'=>'array','allocatable'=>'array','conditions'=>'array','last_heartbeat_at'=>'datetime'];} public function isReady():bool{return $this->status==='Ready';} public function isSchedulable():bool{return $this->schedulable&&$this->isReady();} }
