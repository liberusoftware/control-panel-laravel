<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\AccountsLivewire\Components;
use Illuminate\Contracts\View\View; use Liberu\ControlPanel\Accounts\Models\AccountDelegation; use Liberu\ControlPanel\Accounts\Models\HostingPackage; use Livewire\Component;
final class AccountFeatureInventory extends Component { public function render():View { $teamId=auth()->user()?->current_team_id; abort_if($teamId===null,403,'A current team is required.'); return view('control-panel-accounts-livewire::components.feature-inventory',['packages'=>HostingPackage::where('team_id',$teamId)->latest()->limit(25)->get(),'delegations'=>AccountDelegation::where('team_id',$teamId)->latest()->limit(25)->get()]); } }
