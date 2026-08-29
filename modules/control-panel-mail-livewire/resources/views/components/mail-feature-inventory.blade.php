<section>
    <h2>{{ __('Mail domains') }}</h2>
    <ul>
        @forelse ($domains as $domain)
            <li wire:key="domain-{{ $domain->getKey() }}">{{ $domain->domain }} — {{ $domain->status }}</li>
        @empty
            <li>{{ __('No mail domains found.') }}</li>
        @endforelse
    </ul>
    <h2>{{ __('Mail routes') }}</h2>
    <ul>
        @forelse ($routes as $route)
            <li wire:key="route-{{ $route->getKey() }}">{{ $route->domain }} — {{ $route->source_pattern }} → {{ $route->destination }}</li>
        @empty
            <li>{{ __('No mail routes found.') }}</li>
        @endforelse
    </ul>
    <h2>{{ __('Mail aliases and delivery diagnostics') }}</h2>
    <ul>@forelse ($aliases as $alias)<li wire:key="alias-{{ $alias->getKey() }}">{{ $alias->address }}@{{ $alias->domain }} <form wire:submit="updateAlias('{{ $alias->getKey() }}', null)"><input aria-label="{{ __('Alias domain') }}" wire:model="aliasEdits.{{ $alias->getKey() }}.domain" value="{{ $alias->domain }}"><input aria-label="{{ __('Alias address') }}" wire:model="aliasEdits.{{ $alias->getKey() }}.address" value="{{ $alias->address }}"><button type="submit">{{ __('Save') }}</button></form> <button type="button" wire:click="deleteAlias('{{ $alias->getKey() }}')">{{ __('Delete') }}</button></li>@empty<li>{{ __('No aliases found.') }}</li>@endforelse</ul>
    {{ $aliases->links() }}
    <h3>{{ __('Recent diagnostics') }}</h3><ul>@forelse ($diagnostics as $diagnostic)<li wire:key="diagnostic-{{ $diagnostic->getKey() }}">{{ $diagnostic->recipient }} — {{ $diagnostic->status }}</li>@empty<li>{{ __('No diagnostics found.') }}</li>@endforelse</ul>
    <h3>{{ __('DKIM keys') }}</h3>
    <ul>
        @forelse ($dkimKeys as $key)
            <li wire:key="dkim-{{ $key->getKey() }}">{{ $key->selector }}._domainkey.{{ $key->domain }} — {{ __('active') }}
                <button type="button" wire:click="rotateDkim('{{ $key->domain }}')">{{ __('Rotate') }}</button>
            </li>
        @empty
            <li>{{ __('No DKIM keys found.') }}</li>
        @endforelse
    </ul>
</section>
