<section>
    <h2>{{ __('DNS zones') }}</h2>
    <label for="zone-search">{{ __("Search zones") }}</label><input id="zone-search" type="search" wire:model.live.debounce.300ms="search">
    <ul>
        @forelse ($zones as $zone)
            <li wire:key="zone-{{ $zone->getKey() }}">{{ $zone->domain }} — {{ $zone->status->value }} <form wire:submit="update('{{ $zone->getKey() }}', null)"><input aria-label="{{ __('Zone domain') }}" wire:model="edits.{{ $zone->getKey() }}.domain" value="{{ $zone->domain }}"><input aria-label="{{ __('DNS provider') }}" wire:model="edits.{{ $zone->getKey() }}.provider" value="{{ $zone->provider }}"><button type="submit">{{ __('Save') }}</button></form> @if ($zone->status->value !== 'suspended' && $zone->status->value !== 'archived') <button type="button" wire:click="suspend('{{ $zone->getKey() }}')">{{ __('Suspend') }}</button> @endif @if ($zone->status->value !== 'archived') <button type="button" wire:click="archive('{{ $zone->getKey() }}')">{{ __('Archive') }}</button> @endif</li>
        @empty
            <li>{{ __('No zones found.') }}</li>
        @endforelse
    </ul>
    {{ $zones->links() }}
</section>
