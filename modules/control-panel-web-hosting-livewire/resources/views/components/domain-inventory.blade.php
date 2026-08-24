<section>
    <h2>{{ __('Domains') }}</h2>
    <ul>
        @foreach ($domains as $domain)
            <li wire:key="domain-{{ $domain->getKey() }}">{{ $domain->hostname }} — {{ $domain->status->value }}</li>
        @endforeach
    </ul>
    {{ $domains->links() }}
</section>
