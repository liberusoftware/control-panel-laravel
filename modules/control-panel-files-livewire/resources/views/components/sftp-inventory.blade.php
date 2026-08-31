<section>
    <h2>{{ __('SFTP accounts') }}</h2>
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search SFTP accounts') }}">
    <ul>
        @forelse ($accounts as $account)
            <li wire:key="sftp-{{ $account->getKey() }}">{{ $account->username }} — {{ $account->home_directory }} <button type="button" wire:click="delete('{{ $account->getKey() }}')">{{ __('Delete') }}</button></li>
        @empty
            <li>{{ __('No SFTP accounts found.') }}</li>
        @endforelse
    </ul>
    {{ $accounts->links() }}
</section>
