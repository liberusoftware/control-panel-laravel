<section aria-labelledby="control-panel-account-inventory">
    <h2 id="control-panel-account-inventory">Accounts</h2>

    @if ($accounts->isEmpty())
        <p>No accounts are available for the current team.</p>
    @else
        <ul>
            @foreach ($accounts as $account)
                <li wire:key="account-{{ $account->getKey() }}">
                    <span>{{ $account->name }}</span>
                    <span>{{ $account->type->value }}</span>
                    <span>{{ $account->status->value }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
