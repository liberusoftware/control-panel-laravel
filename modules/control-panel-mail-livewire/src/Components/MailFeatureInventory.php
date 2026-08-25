<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\Mail\Models\DeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Models\DkimKey;
use Liberu\ControlPanel\Mail\Models\MailAlias;
use Liberu\ControlPanel\Mail\Models\MailDomain;
use Livewire\Component;

final class MailFeatureInventory extends Component
{
    public int $perPage = 25;

    public function rotateDkim(string $domain, RotateDkimKey $rotate): void
    {
        $rotate->execute((string) auth()->user()?->current_team_id, $domain);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;

        return view('control-panel-mail-livewire::components.mail-feature-inventory', [
            'aliases' => MailAlias::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100), ['*'], 'aliases_page'),
            'domains' => MailDomain::query()->where('team_id', $teamId)->latest()->get(),
            'diagnostics' => DeliveryDiagnostic::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'dkimKeys' => DkimKey::query()->where('team_id', $teamId)->where('active', true)->latest()->get(),
        ]);
    }
}
