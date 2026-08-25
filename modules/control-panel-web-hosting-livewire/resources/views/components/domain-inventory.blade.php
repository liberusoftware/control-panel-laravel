<section>
    <h2>{{ __('Domains') }}</h2>
    <label for="domain-search">{{ __('Search domains') }}</label>
    <input id="domain-search" type="search" wire:model.live.debounce.300ms="search">
    <ul>
        @foreach ($domains as $domain)
            <li wire:key="domain-{{ $domain->getKey() }}">{{ $domain->hostname }} — {{ $domain->status->value }}</li>
        @endforeach
    </ul>
    {{ $domains->links() }}
</section>
