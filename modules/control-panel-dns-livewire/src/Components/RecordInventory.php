<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\Dns\Actions\CreateRecord;
use Liberu\ControlPanel\Dns\Actions\DeleteRecord;
use Liberu\ControlPanel\Dns\Actions\UpdateRecord;
use Liberu\ControlPanel\Dns\Models\Record;
use Livewire\Component;
use Livewire\WithPagination;

final class RecordInventory extends Component
{
    use WithPagination;

    public int $perPage = 25;

    public string $zoneId = '';

    public string $name = '@';

    public string $type = 'A';

    public string $content = '';

    public int $ttl = 3600;

    public ?int $priority = null;

    /** @var array<string, array<string, mixed>> */
    public array $edits = [];

    public function save(CreateRecord $create): void
    {
        $teamId = $this->teamId();
        $this->validate([
            'zoneId' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:253'],
            'type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'], 'content' => ['required', 'string', 'max:4096'],
            'ttl' => ['required', 'integer', 'min:60', 'max:86400'], 'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
        $create->execute(['team_id' => $teamId, 'zone_id' => $this->zoneId, 'name' => $this->name, 'type' => $this->type, 'content' => $this->content, 'ttl' => $this->ttl, 'priority' => $this->priority]);
        $this->reset(['content', 'priority']);
        $this->resetPage();
    }

    /** @param array<string, mixed>|null $attributes */
    public function update(string $recordId, ?array $attributes, UpdateRecord $update): void
    {
        $record = Record::query()->with('zone')->whereKey($recordId)->whereHas('zone', fn ($query) => $query->where('team_id', $this->teamId()))->firstOrFail();
        $attributes ??= $this->edits[$recordId] ?? [];
        validator($attributes, [
            'zone_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:253'],
            'type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA'], 'content' => ['required', 'string', 'max:4096'],
            'ttl' => ['required', 'integer', 'between:60,86400'], 'priority' => ['nullable', 'integer', 'between:0,65535'],
        ])->validate();
        $update->execute($record, $attributes);
        unset($this->edits[$recordId]);
    }

    public function delete(string $recordId, DeleteRecord $delete): void
    {
        $record = Record::query()->whereKey($recordId)->whereHas('zone', fn ($query) => $query->where('team_id', $this->teamId()))->firstOrFail();
        $delete->execute($record);
        unset($this->edits[$recordId]);
    }

    public function render(): View
    {
        $records = Record::query()->with('zone')->whereHas('zone', fn ($query) => $query->where('team_id', $this->teamId()))->latest()->paginate(min(max($this->perPage, 1), 100));

        return view('control-panel-dns-livewire::components.record-inventory', ['records' => $records]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
