<section>
    <h2>{{ __('Maintenance windows') }}</h2>
    <ul>
        @forelse ($maintenance as $window)
            <li wire:key="maintenance-{{ $window->getKey() }}">{{ $window->name }} — {{ $window->status }} @if (! in_array($window->status, ['cancelled', 'completed'], true))<button type="button" wire:click="cancelMaintenance('{{ $window->getKey() }}')" wire:loading.attr="disabled">{{ __('Cancel') }}</button>@endif</li>
        @empty
            <li>{{ __('No maintenance windows found.') }}</li>
        @endforelse
    </ul>
    <h2>{{ __('Monitoring events') }}</h2>
    <ul>@forelse($events as $event)<li wire:key="monitoring-event-{{ $event->getKey() }}">{{ $event->kind }} — {{ $event->status }} @if($event->kind === 'incident' && $event->status === 'open') <button type="button" wire:click="resolveEvent('{{ $event->getKey() }}')">{{ __('Resolve') }}</button> @endif</li>@empty<li>{{ __('No monitoring events found.') }}</li>@endforelse</ul>{{ $events->links() }}
</section>
