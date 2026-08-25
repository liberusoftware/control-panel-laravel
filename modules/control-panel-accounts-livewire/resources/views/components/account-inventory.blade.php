<section aria-labelledby="control-panel-account-inventory">
    <h2 id="control-panel-account-inventory">Accounts</h2>

    <label for="control-panel-account-search">Search accounts</label>
    <input id="control-panel-account-search" type="search" wire:model.live.debounce.300ms="search" autocomplete="off">
    <label for="control-panel-suspension-reason">Suspension reason</label>
    <input id="control-panel-suspension-reason" type="text" wire:model="suspensionReason" maxlength="1000">

    @if ($accounts->isEmpty())
        <p>No accounts are available for the current team.</p>
    @else
        <ul>
            @foreach ($accounts as $account)
                <li wire:key="account-{{ $account->getKey() }}">
                    <span>{{ $account->name }}</span>
                    <span>{{ $account->type->value }}</span>
                    <span>{{ $account->status->value }}</span>
                    <span>{{ count($account->quota_overrides ?? []) }} quota limits</span>
                    @if ($account->status->value === 'active')
                        <button type="button" wire:click="suspend('{{ $account->getKey() }}')">Suspend</button>
                    @elseif ($account->status->value === 'suspended')
                        <button type="button" wire:click="activate('{{ $account->getKey() }}')">Activate</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
