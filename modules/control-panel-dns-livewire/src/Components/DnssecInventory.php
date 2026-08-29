<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Liberu\ControlPanel\Dns\Models\DnssecKey;

final class DnssecInventory extends FeatureInventory
{
    protected function modelClass(): string
    {
        return DnssecKey::class;
    }

    protected function featureName(): string
    {
        return __('DNSSEC keys');
    }

    protected function columns(): array
    {
        return ['key_tag', 'algorithm', 'digest_type', 'active', 'rotated_at'];
    }
}
