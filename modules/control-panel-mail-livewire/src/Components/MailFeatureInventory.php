<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MailLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAlias;
use Liberu\ControlPanel\Mail\Actions\DeleteMailRoute;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\Mail\Actions\UpdateMailAlias;
use Liberu\ControlPanel\Mail\Actions\UpdateMailRoute;
use Liberu\ControlPanel\Mail\Models\DeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Models\DkimKey;
use Liberu\ControlPanel\Mail\Models\MailAlias;
use Liberu\ControlPanel\Mail\Models\MailDomain;
use Liberu\ControlPanel\Mail\Models\MailRoute;
use Livewire\Component;

final class MailFeatureInventory extends Component
{
    public int $perPage = 25;

    /** @var array<string, array<string, mixed>> */
    public array $aliasEdits = [];

    /** @var array<string, array<string, mixed>> */
    public array $routeEdits = [];

    public function rotateDkim(string $domain, RotateDkimKey $rotate): void
    {
        $rotate->execute($this->teamId(), $domain);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateAlias(string $aliasId, ?array $attributes, UpdateMailAlias $update): void
    {
        $alias = MailAlias::query()->whereKey($aliasId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->aliasEdits[$aliasId] ?? [];
        validator($attributes, ['domain' => ['required', 'string', 'max:253'], 'address' => ['required', 'string', 'max:320'], 'destinations' => ['required', 'array', 'min:1'], 'destinations.*' => ['email'], 'active' => ['sometimes', 'boolean']])->validate();
        $update->execute($alias, $attributes);
        unset($this->aliasEdits[$aliasId]);
    }

    public function deleteAlias(string $aliasId, DeleteMailAlias $delete): void
    {
        $alias = MailAlias::query()->whereKey($aliasId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($alias);
        unset($this->aliasEdits[$aliasId]);
    }

    /** @param array<string, mixed>|null $attributes */
    public function updateRoute(string $routeId, ?array $attributes, UpdateMailRoute $update): void
    {
        $route = MailRoute::query()->whereKey($routeId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->routeEdits[$routeId] ?? [];
        validator($attributes, ['domain' => ['required', 'string', 'max:253'], 'source_pattern' => ['required', 'string', 'max:255'], 'destination' => ['required', 'email', 'max:320'], 'priority' => ['required', 'integer', 'min:0'], 'active' => ['sometimes', 'boolean']])->validate();
        $update->execute($route, $attributes);
        unset($this->routeEdits[$routeId]);
    }

    public function deleteRoute(string $routeId, DeleteMailRoute $delete): void
    {
        $route = MailRoute::query()->whereKey($routeId)->where('team_id', $this->teamId())->firstOrFail();
        $delete->execute($route);
        unset($this->routeEdits[$routeId]);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return view('control-panel-mail-livewire::components.mail-feature-inventory', [
            'aliases' => MailAlias::query()->where('team_id', $teamId)->latest()->paginate(min(max($this->perPage, 1), 100), ['*'], 'aliases_page'),
            'domains' => MailDomain::query()->where('team_id', $teamId)->latest()->get(),
            'routes' => MailRoute::query()->where('team_id', $teamId)->orderBy('priority')->latest()->get(),
            'diagnostics' => DeliveryDiagnostic::query()->where('team_id', $teamId)->latest()->limit(10)->get(),
            'dkimKeys' => DkimKey::query()->where('team_id', $teamId)->where('active', true)->latest()->get(),
        ]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
