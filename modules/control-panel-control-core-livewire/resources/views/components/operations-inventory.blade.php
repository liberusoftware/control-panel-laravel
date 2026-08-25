<section aria-labelledby="control-core-operations-inventory">
    <h2 id="control-core-operations-inventory">Control Core operations</h2>
    <p>{{ $tasks->count() }} tasks, {{ $inventory->count() }} inventory records, {{ $locks->count() }} locks, {{ $audit->count() }} audit entries.</p>

    @if ($tasks->isNotEmpty())
        <ul>
            @foreach ($tasks as $task)
                <li wire:key="operation-task-{{ $task->getKey() }}">
                    <span>{{ $task->operation }} — {{ $task->status->value }}</span>
                    @if ($task->status->value === 'pending')
                        <button type="button" wire:click="transitionTask('{{ $task->getKey() }}', 'running')">Start</button>
                    @elseif ($task->status->value === 'running')
                        <button type="button" wire:click="transitionTask('{{ $task->getKey() }}', 'succeeded')">Succeed</button>
                        <button type="button" wire:click="transitionTask('{{ $task->getKey() }}', 'failed')">Fail</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($locks->isNotEmpty())
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
</section>
