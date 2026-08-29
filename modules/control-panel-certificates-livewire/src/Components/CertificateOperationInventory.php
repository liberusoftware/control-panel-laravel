<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Certificates\Actions\CheckCertificateExpiry;
use Liberu\ControlPanel\Certificates\Actions\ExpireCertificate;
use Liberu\ControlPanel\Certificates\Actions\RequestCertificateRenewal;
use Liberu\ControlPanel\Certificates\Actions\RevokeCertificate;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Liberu\ControlPanel\Certificates\Models\CertificateOperation;
use Livewire\Component;
use Livewire\WithPagination;

final class CertificateOperationInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public function renew(string $certificateId, RequestCertificateRenewal $renew): void
    {
        $certificate = Certificate::query()
            ->whereKey($certificateId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $renew->execute($certificate);
        $this->resetPage();
    }

    public function checkExpiry(string $certificateId, CheckCertificateExpiry $check): void
    {
        $certificate = Certificate::query()
            ->whereKey($certificateId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $check->execute($certificate);
    }

    public function revoke(string $certificateId, RevokeCertificate $revoke): void
    {
        $certificate = Certificate::query()
            ->whereKey($certificateId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $revoke->execute($certificate);
    }

    public function expire(string $certificateId, ExpireCertificate $expire): void
    {
        $certificate = Certificate::query()
            ->whereKey($certificateId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $expire->execute($certificate);
    }

    public function render(): View
    {
        $certificates = Certificate::query()
            ->where('team_id', $this->teamId())
            ->latest()
            ->get();
        $operations = CertificateOperation::query()->where('team_id', $this->teamId())->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-certificates-livewire::components.certificate-operation-inventory', compact('certificates', 'operations'));
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
