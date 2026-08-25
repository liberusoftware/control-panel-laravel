<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\CertificatesLivewire\Components;
use Illuminate\Contracts\View\View; use Liberu\ControlPanel\Certificates\Models\CertificateOperation; use Livewire\Component;
final class CertificateOperationInventory extends Component { public int $perPage=25; public function render():View { $operations=CertificateOperation::query()->where('team_id',auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage,1),100)); return view('control-panel-certificates-livewire::components.certificate-operation-inventory',['operations'=>$operations]); } }
