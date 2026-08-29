<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Liberu\ControlPanel\Dns\Models\DnsProvider;

final class DnsProviderInventory extends FeatureInventory
{
    protected function modelClass(): string
    {
        return DnsProvider::class;
    }

    protected function featureName(): string
    {
        return __('DNS providers');
    }

    protected function columns(): array
    {
        return ['name', 'driver', 'endpoint', 'active'];
    }
}
