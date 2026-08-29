<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreLivewire\Components;

use Illuminate\Contracts\View\View;
use Liberu\ControlPanel\ControlCore\Actions\ExpireNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\GenerateSshKeyPair;
use Liberu\ControlPanel\ControlCore\Actions\RegisterNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\RevokeNodeCredential;
use Liberu\ControlPanel\ControlCore\Actions\UpdateNodeCredential;
use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
use Livewire\Component;
use Livewire\WithPagination;

final class CredentialInventory extends Component
{
    use WithPagination;

    public string $nodeId = '';

    public string $name = '';

    public string $username = '';

    public string $publicKey = '';

    public string $generatedPrivateKey = '';

    public string $generatedPublicKey = '';

    public string $keyPassphrase = '';

    public int $keyBits = 4096;

    public string $keyComment = '';

    public function generateKeyPair(GenerateSshKeyPair $generate): void
    {
        $data = $this->validate([
            'keyPassphrase' => ['nullable', 'string', 'min:8', 'max:4096'],
            'keyBits' => ['required', 'integer', 'in:2048,4096'],
            'keyComment' => ['nullable', 'string', 'max:255'],
        ]);
        $pair = $generate->execute($data['keyPassphrase'] ?: null, $data['keyBits'], $data['keyComment'] ?: null);
        $this->generatedPrivateKey = $pair['private_key'];
        $this->generatedPublicKey = $pair['public_key'];
    }

    /** @var array<string, array<string, mixed>> */
    public array $edits = [];

    /** @param array<string, mixed>|null $attributes */
    public function update(string $credentialId, ?array $attributes, UpdateNodeCredential $update): void
    {
        $credential = NodeCredential::query()->whereKey($credentialId)->where('team_id', $this->teamId())->firstOrFail();
        $attributes ??= $this->edits[$credentialId] ?? [];
        $attributes = validator($attributes, ['name' => ['required', 'string', 'max:160'], 'username' => ['nullable', 'string', 'alpha_dash', 'max:120'], 'expires_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']])->validate();
        $update->execute($credential, $attributes);
        unset($this->edits[$credentialId]);
    }

    public function createCredential(RegisterNodeCredential $register): void
    {
        $teamId = $this->teamId();
        $data = $this->validate([
            'nodeId' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:160'],
            'username' => ['nullable', 'string', 'alpha_dash', 'max:120'],
            'publicKey' => ['required', 'string', 'max:10000'],
        ]);

        $register->execute([
            'team_id' => $teamId,
            'node_id' => $data['nodeId'],
            'name' => $data['name'],
            'type' => 'ssh',
            'username' => $data['username'] ?: null,
            'public_key' => $data['publicKey'],
        ]);

        $this->reset(['nodeId', 'name', 'username', 'publicKey']);
        $this->resetPage();
    }

    public function revoke(string $credentialId, RevokeNodeCredential $revoke): void
    {
        $credential = NodeCredential::query()
            ->whereKey($credentialId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $revoke->execute($credential);
    }

    public function expire(string $credentialId, ExpireNodeCredential $expire): void
    {
        $credential = NodeCredential::query()
            ->whereKey($credentialId)
            ->where('team_id', $this->teamId())
            ->firstOrFail();

        $expire->execute($credential);
    }

    public function render(): View
    {
        $teamId = $this->teamId();
        $credentials = NodeCredential::query()->where('team_id', $teamId)->latest()->paginate(25);

        return view('control-panel-control-core-livewire::components.credential-inventory', ['credentials' => $credentials]);
    }

    private function teamId(): string
    {
        $teamId = auth()->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }
}
