<section aria-labelledby="control-core-operations-inventory">
    <h2 id="control-core-operations-inventory">Control Core operations</h2>
    <p wire:loading role="status">Loading operations…</p>
    <p>{{ $tasks->count() }} tasks, {{ $inventory->count() }} inventory records, {{ $locks->count() }} locks, {{ $audit->count() }} audit entries.</p>

    @if ($tasks->isEmpty())
        <p>No operation tasks are available for the current team.</p>
    @else
        <ul>
            @foreach ($tasks as $task)
                <li wire:key="operation-task-{{ $task->getKey() }}">
                    <span>{{ $task->operation }} — {{ $task->status->value }} ({{ $task->steps_count }} steps{{ $task->timeout_at ? ', timeout '.$task->timeout_at->toDateTimeString() : '' }}, {{ $task->compensation_status->value }} compensation)</span>
                    @if ($task->status->value === 'pending')
                        <button type="button" wire:click="transitionTask('{{ $task->getKey() }}', 'running')">Start</button>
                        <button type="button" wire:click="cancelTask('{{ $task->getKey() }}')">Cancel</button>
                    @elseif ($task->status->value === 'running')
                        <button type="button" wire:click="transitionTask('{{ $task->getKey() }}', 'succeeded')">Succeed</button>
                        <button type="button" wire:click="transitionTask('{{ $task->getKey() }}', 'failed')">Fail</button>
                        <button type="button" wire:click="cancelTask('{{ $task->getKey() }}')">Cancel</button>
                    @elseif ($task->status->value === 'failed')
                        <button type="button" wire:click="retryTask('{{ $task->getKey() }}')">Retry</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($locks->isEmpty())
        <p>No operation locks are available for the current team.</p>
    @else
        <label for="lock-owner">Lock owner</label>
        <input id="lock-owner" type="text" wire:model="lockOwner">
        <ul>
            @foreach ($locks as $lock)
                <li wire:key="operation-lock-{{ $lock->getKey() }}">
                    <span>{{ $lock->operation_key }}</span>
                    <button type="button" wire:click="releaseLock('{{ $lock->getKey() }}')">Release</button>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($inventory->isEmpty())
        <p>No inventory records are available for the current team.</p>
    @endif

    @if ($audit->isEmpty())
        <p>No audit entries are available for the current team.</p>
    @endif
</section>
