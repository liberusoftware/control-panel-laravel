<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Kubernetes\Models;
use Illuminate\Database\Eloquent\Model;
final class KubernetesClusterView extends Model { protected $table='control_panel_kubernetes_cluster_views'; protected $fillable=['id','team_id','name','cluster_ids','filters','status']; protected function casts():array{return ['cluster_ids'=>'array','filters'=>'array'];} }
