<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\ControlCoreLivewire\Components;
use Illuminate\Contracts\View\View; use Liberu\ControlPanel\ControlCore\Models\AuditEntry; use Liberu\ControlPanel\ControlCore\Models\InventoryRecord; use Liberu\ControlPanel\ControlCore\Models\OperationLock; use Liberu\ControlPanel\ControlCore\Models\OperationTask; use Livewire\Component;
final class OperationsInventory extends Component { public function render():View { $teamId=auth()->user()?->current_team_id; abort_if($teamId===null,403,'A current team is required.'); return view('control-panel-control-core-livewire::components.operations-inventory',['tasks'=>OperationTask::where('team_id',$teamId)->latest()->limit(25)->get(),'inventory'=>InventoryRecord::where('team_id',$teamId)->latest('observed_at')->limit(25)->get(),'locks'=>OperationLock::where('team_id',$teamId)->latest()->limit(25)->get(),'audit'=>AuditEntry::where('team_id',$teamId)->latest()->limit(25)->get()]); } }
