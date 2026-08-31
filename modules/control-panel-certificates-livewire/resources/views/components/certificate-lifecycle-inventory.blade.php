<section>
    <h2>{{ __('Certificate lifecycle') }}</h2>
    @foreach ([__('ACME accounts') => $acmeAccounts, __('Deployments') => $deployments, __('Renewals') => $renewals, __('Expiry alerts') => $expiryAlerts] as $label => $items)
        <h3>{{ $label }}</h3>
        <ul>
            @forelse ($items as $item)
                <li wire:key="{{ str($label)->slug() }}-{{ $item->getKey() }}">
                    {{ $item->email ?? $item->certificate_id ?? $item->getKey() }}
                    @if ($item->status ?? false) — {{ is_object($item->status) ? $item->status->value : $item->status }} @endif
                </li>
            @empty
                <li>{{ __('No records found.') }}</li>
            @endforelse
        </ul>
    @endforeach
</section>
