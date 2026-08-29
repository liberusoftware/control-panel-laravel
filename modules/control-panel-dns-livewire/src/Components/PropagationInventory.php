<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Liberu\ControlPanel\Dns\Models\PropagationCheck;

final class PropagationInventory extends FeatureInventory
{
    protected function modelClass(): string
    {
        return PropagationCheck::class;
    }

    protected function featureName(): string
    {
        return __('DNS propagation checks');
    }

    protected function columns(): array
    {
        return ['zone_id', 'record_id', 'status', 'checked_at'];
    }
}
