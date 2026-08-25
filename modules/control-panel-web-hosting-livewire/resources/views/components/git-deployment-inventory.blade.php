<section aria-labelledby="web-hosting-git-deployment-inventory">
    <h2 id="web-hosting-git-deployment-inventory">Git deployments</h2>
    <label for="deployment-search">Search deployments</label><input id="deployment-search" type="search" wire:model.live.debounce.300ms="search">
    @if ($deployments->isEmpty())
        <p>No Git deployments are configured.</p>
    @else
        <ul>
            @foreach ($deployments as $deployment)
                <li wire:key="git-deployment-{{ $deployment->getKey() }}">{{ $deployment->repository_name }} — {{ $deployment->branch }} — {{ $deployment->status }} <button type="button" wire:click="deploy('{{ $deployment->getKey() }}')">{{ __('Queue deployment') }}</button></li>
            @endforeach
        </ul>
        {{ $deployments->links() }}
    @endif
</section>
