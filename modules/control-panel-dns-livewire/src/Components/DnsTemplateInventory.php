<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Liberu\ControlPanel\Dns\Models\DnsTemplate;

final class DnsTemplateInventory extends FeatureInventory
{
    protected function modelClass(): string
    {
        return DnsTemplate::class;
    }

    protected function featureName(): string
    {
        return __('DNS templates');
    }

    protected function columns(): array
    {
        return ['name', 'active'];
    }
}
