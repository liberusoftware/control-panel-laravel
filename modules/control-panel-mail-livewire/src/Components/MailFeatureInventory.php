<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\MailLivewire\Components;
use Illuminate\Contracts\View\View; use Liberu\ControlPanel\Mail\Models\MailAlias; use Liberu\ControlPanel\Mail\Models\DeliveryDiagnostic; use Livewire\Component;
final class MailFeatureInventory extends Component { public int $perPage=25; public function render():View { $teamId=auth()->user()?->current_team_id; return view('control-panel-mail-livewire::components.mail-feature-inventory',['aliases'=>MailAlias::query()->where('team_id',$teamId)->latest()->paginate(min(max($this->perPage,1),100),['*'],'aliases_page'),'diagnostics'=>DeliveryDiagnostic::query()->where('team_id',$teamId)->latest()->limit(10)->get()]); } }
