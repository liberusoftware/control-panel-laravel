<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Livewire\Component;

final class CertificateInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $certificates = Certificate::query()->where('team_id', auth()->user()?->current_team_id)->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-certificates-livewire::components.certificate-inventory', ['certificates' => $certificates]);
    }
}
