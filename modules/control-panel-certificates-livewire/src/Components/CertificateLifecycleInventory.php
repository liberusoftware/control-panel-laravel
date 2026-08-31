<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Certificates\Models\AcmeAccount;
use Liberu\ControlPanel\Certificates\Models\CertificateDeployment;
use Liberu\ControlPanel\Certificates\Models\CertificateExpiryAlert;
use Liberu\ControlPanel\Certificates\Models\CertificateRenewal;
use Livewire\Component;

final class CertificateLifecycleInventory extends Component
{
    public int $perPage = 25;

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');
        $limit = min(max($this->perPage, 1), 100);

        return view('control-panel-certificates-livewire::components.certificate-lifecycle-inventory', [
            'acmeAccounts' => AcmeAccount::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
            'deployments' => CertificateDeployment::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
            'renewals' => CertificateRenewal::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
            'expiryAlerts' => CertificateExpiryAlert::query()->where('team_id', $teamId)->latest()->limit($limit)->get(),
        ]);
    }
}
