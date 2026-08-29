<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ApiAutomation\Actions\PauseWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\ResumeWebhook;
use Liberu\ControlPanel\ApiAutomation\Actions\UpdateWebhook;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;
use Liberu\ControlPanel\ApiAutomation\Queries\ListWebhooks;
use Livewire\Component;
use Livewire\WithPagination;

final class WebhookInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $search = '';

    /** @var array<string, array<string, mixed>> */
    public array $edits = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function pause(string $webhookId, PauseWebhook $pause): void
    {
        $webhook = WebhookEndpoint::query()
            ->whereKey($webhookId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $pause->execute($webhook);
    }

    public function resume(string $webhookId, ResumeWebhook $resume): void
    {
        $webhook = WebhookEndpoint::query()
            ->whereKey($webhookId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $resume->execute($webhook);
    }

    /** @param array<string, mixed>|null $attributes */
    public function update(string $webhookId, ?array $attributes, UpdateWebhook $update): void
    {
        $webhook = WebhookEndpoint::query()
            ->whereKey($webhookId)
            ->where('team_id', auth()->user()?->current_team_id)
            ->firstOrFail();
        $attributes ??= $this->edits[$webhookId] ?? [];
        validator($attributes, [
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'starts_with:https://', 'max:2048'],
            'events' => ['nullable', 'array'],
            'retry_limit' => ['required', 'integer', 'between:0,20'],
        ])->validate();
        $update->execute($webhook, $attributes);
        unset($this->edits[$webhookId]);
    }

    public function render(ListWebhooks $list): View
    {
        abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');

        return view('control-panel-api-and-automation-livewire::components.webhook-inventory', ['webhooks' => $list->execute(auth()->user()->current_team_id, min(max($this->perPage, 1), 100), $this->search)]);
    }
}
