<section aria-labelledby="web-hosting-git-deployment-inventory">
    <h2 id="web-hosting-git-deployment-inventory">Git deployments</h2>
    @if ($deployments->isEmpty())
        <p>No Git deployments are configured.</p>
    @else
        <ul>
            @foreach ($deployments as $deployment)
                <li>{{ $deployment->repository_name }} — {{ $deployment->branch }} — {{ $deployment->status }}</li>
            @endforeach
        </ul>
        {{ $deployments->links() }}
    @endif
</section>
