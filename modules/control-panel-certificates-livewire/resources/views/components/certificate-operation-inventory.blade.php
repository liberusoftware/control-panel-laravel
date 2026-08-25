<section>
    <h2>{{ __('Certificates') }}</h2>
    <ul>
        @forelse($certificates as $certificate)
            <li wire:key="certificate-{{ $certificate->getKey() }}">
                {{ implode(', ', (array) $certificate->domains) }} — {{ $certificate->status->value }}
                <button type="button" wire:click="renew('{{ $certificate->getKey() }}')">{{ __('Renew') }}</button>
                <button type="button" wire:click="checkExpiry('{{ $certificate->getKey() }}')">{{ __('Check expiry') }}</button>
                @if ($certificate->status->value !== 'revoked')
                    <button type="button" wire:click="revoke('{{ $certificate->getKey() }}')">{{ __('Revoke') }}</button>
                @endif
            </li>
        @empty
            <li>{{ __('No certificates found.') }}</li>
        @endforelse
    </ul>

    <h2>{{ __('Certificate operations') }}</h2>
    <ul>
        @forelse($operations as $operation)
            <li wire:key="certificate-operation-{{ $operation->getKey() }}">{{ $operation->operation }} — {{ $operation->status }}</li>
        @empty
            <li>{{ __('No certificate operations found.') }}</li>
        @endforelse
    </ul>
    {{ $operations->links() }}
</section>
