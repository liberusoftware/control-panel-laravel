<section>
    <h2>{{ __('Mail aliases and delivery diagnostics') }}</h2>
    <ul>@forelse ($aliases as $alias)<li wire:key="alias-{{ $alias->getKey() }}">{{ $alias->address }}@{{ $alias->domain }}</li>@empty<li>{{ __('No aliases found.') }}</li>@endforelse</ul>
    {{ $aliases->links() }}
    <h3>{{ __('Recent diagnostics') }}</h3><ul>@forelse ($diagnostics as $diagnostic)<li wire:key="diagnostic-{{ $diagnostic->getKey() }}">{{ $diagnostic->recipient }} — {{ $diagnostic->status }}</li>@empty<li>{{ __('No diagnostics found.') }}</li>@endforelse</ul>
</section>
