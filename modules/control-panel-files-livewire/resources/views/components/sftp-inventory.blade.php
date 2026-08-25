<section>
    <h2>{{ __('SFTP accounts') }}</h2>
    <ul>
        @forelse ($accounts as $account)
            <li wire:key="sftp-{{ $account->getKey() }}">{{ $account->username }} — {{ $account->home_directory }}</li>
        @empty
            <li>{{ __('No SFTP accounts found.') }}</li>
        @endforelse
    </ul>
    {{ $accounts->links() }}
</section>
