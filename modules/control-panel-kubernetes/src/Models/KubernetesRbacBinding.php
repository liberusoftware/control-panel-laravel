<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Kubernetes\Models;
use Illuminate\Database\Eloquent\Model;
final class KubernetesRbacBinding extends Model { protected $table='control_panel_kubernetes_rbac'; protected $fillable=['id','team_id','cluster_id','namespace','name','role','subjects','rules','active']; protected function casts():array{return ['subjects'=>'array','rules'=>'array','active'=>'boolean'];} }
