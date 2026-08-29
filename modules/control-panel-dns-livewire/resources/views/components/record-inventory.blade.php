<section aria-labelledby="dns-record-inventory">
    <h2 id="dns-record-inventory">{{ __('DNS records') }}</h2>
    <form wire:submit="save">
        <label for="dns-record-zone">{{ __('Zone') }}</label><input id="dns-record-zone" type="text" wire:model="zoneId">
        <label for="dns-record-name">{{ __('Name') }}</label><input id="dns-record-name" type="text" wire:model="name">
        <label for="dns-record-type">{{ __('Type') }}</label><input id="dns-record-type" type="text" wire:model="type">
        <label for="dns-record-content">{{ __('Content') }}</label><input id="dns-record-content" type="text" wire:model="content">
        <label for="dns-record-ttl">{{ __('TTL') }}</label><input id="dns-record-ttl" type="number" wire:model="ttl">
        <button type="submit">{{ __('Create record') }}</button>
    </form>
    <ul>
        @forelse ($records as $record)
            <li wire:key="dns-record-{{ $record->getKey() }}">{{ $record->name }} {{ $record->type }} {{ $record->content }} ({{ $record->zone->domain }})</li>
        @empty
            <li>{{ __('No DNS records found.') }}</li>
        @endforelse
    </ul>
    {{ $records->links() }}
</section>
