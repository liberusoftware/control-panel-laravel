<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\CertificatesLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Certificates\Actions\UpdateCertificate;
use Liberu\ControlPanel\Certificates\Models\Certificate;
use Livewire\Component;

final class CertificateInventory extends Component
{
    public int $perPage = 25;

    /** @param array<string, mixed> $attributes */
    public function update(string $certificateId, array $attributes, UpdateCertificate $update): void
    {
        $certificate = Certificate::query()->whereKey($certificateId)->where('team_id', $this->teamId())->firstOrFail();
        $update->execute($certificate, $attributes);
    }

    public function render(): View
    {
        $certificates = Certificate::query()->where('team_id', $this->teamId())->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-certificates-livewire::components.certificate-inventory', ['certificates' => $certificates]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
