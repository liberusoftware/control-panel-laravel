<section aria-labelledby="os-firewall-rule-inventory-heading">
    <h2 id="os-firewall-rule-inventory-heading">{{ __('Firewall rules') }}</h2>
    <form wire:submit="createRule" aria-label="Create firewall rule">
        <input type="text" wire:model="nodeId" placeholder="Node ID" required>
        <select wire:model="direction"><option value="inbound">Inbound</option><option value="outbound">Outbound</option></select>
        <select wire:model="action"><option value="allow">Allow</option><option value="deny">Deny</option><option value="reject">Reject</option></select>
        <input type="text" wire:model="protocol" placeholder="Protocol">
        <input type="number" wire:model="port" min="1" max="65535" placeholder="Port">
        <input type="text" wire:model="source" maxlength="64" placeholder="Source IP/CIDR">
        <input type="text" wire:model="comment" maxlength="255" placeholder="Comment">
        <button type="submit" wire:loading.attr="disabled">{{ __('Create rule') }}</button>
    </form>
    @if ($error)<p role="alert">{{ $error }}</p>@endif
    <p wire:loading role="status">{{ __('Saving firewall rule…') }}</p>
    @error('source') <p role="alert">{{ $message }}</p> @enderror
    <ul>
        @forelse($rules as $rule)
            <li wire:key="firewall-rule-{{ $rule->getKey() }}">
                <span>{{ $rule->direction }} {{ $rule->action }} {{ $rule->protocol }} {{ $rule->port }} {{ $rule->source }}</span>
                <button type="button" wire:click="delete('{{ $rule->getKey() }}')">{{ __('Delete') }}</button>
            </li>
        @empty
            <li>{{ __('No firewall rules found.') }}</li>
        @endforelse
    </ul>
    {{ $rules->links() }}
</section>
