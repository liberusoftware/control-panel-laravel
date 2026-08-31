<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersLivewire\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\OsAdapters\Actions\CreateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\DeleteFirewallRule;
use Liberu\ControlPanel\OsAdapters\Actions\UpdateFirewallRule;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;
use Livewire\Component;
use Livewire\WithPagination;

final class FirewallRuleInventory extends Component
{
    use WithPagination;

    public string $nodeId = '';

    public string $direction = 'inbound';

    public string $action = 'allow';

    public string $protocol = 'tcp';

    public string $port = '';

    public string $source = '';

    public string $comment = '';

    public string $error = '';

    /** @var array<string, array<string, mixed>> */
    public array $edits = [];

    public function createRule(CreateFirewallRule $create): void
    {
        $this->runSafely(function () use ($create): void {
            $teamId = $this->teamId();
            $data = $this->validate($this->rules());
            $create->execute(array_merge($data, ['team_id' => $teamId, 'node_id' => $data['nodeId'], 'port' => $data['port'] ?: null, 'source' => $data['source'] ?: null, 'comment' => $data['comment'] ?: null]));
            $this->reset(['nodeId', 'port', 'source', 'comment']);
            $this->resetPage();
        });
    }

    public function update(string $ruleId, ?array $attributes, UpdateFirewallRule $update): void
    {
        $this->runSafely(function () use ($ruleId, $attributes, $update): void {
            $rule = FirewallRule::query()->whereKey($ruleId)->where('team_id', $this->teamId())->firstOrFail();
            $attributes ??= $this->edits[$ruleId] ?? [];
            $attributes = validator($attributes, [
                'direction' => ['required', 'in:inbound,outbound'],
                'action' => ['required', 'in:allow,deny,reject'],
                'protocol' => ['nullable', 'string', 'max:20'],
                'port' => ['nullable', 'integer', 'between:1,65535'],
                'source' => ['nullable', 'string', 'max:64'],
                'comment' => ['nullable', 'string', 'max:255'],
                'active' => ['sometimes', 'boolean'],
            ])->validate();
            $update->execute($rule, $attributes);
            unset($this->edits[$ruleId]);
        });
    }

    public function delete(string $ruleId, DeleteFirewallRule $delete): void
    {
        $this->runSafely(function () use ($ruleId, $delete): void {
            $rule = FirewallRule::query()->whereKey($ruleId)->where('team_id', $this->teamId())->firstOrFail();
            $delete->execute($rule);
        });
    }

    public function render(): View
    {
        return view('control-panel-os-adapters-livewire::components.firewall-rule-inventory', ['rules' => FirewallRule::query()->where('team_id', $this->teamId())->latest()->paginate(25)]);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'nodeId' => ['required', 'uuid'],
            'direction' => ['required', 'in:inbound,outbound'],
            'action' => ['required', 'in:allow,deny,reject'],
            'protocol' => ['nullable', 'string', 'max:20'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'source' => ['nullable', 'string', 'max:64'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }

    private function runSafely(\Closure $operation): void
    {
        $this->error = '';

        try {
            $operation();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error = __('The firewall rule operation could not be completed. Please try again.');
        }
    }
}
