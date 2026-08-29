<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsLivewire\Components;

use Liberu\ControlPanel\Dns\Models\DnsValidation;

final class DnsValidationInventory extends FeatureInventory
{
    protected function modelClass(): string
    {
        return DnsValidation::class;
    }

    protected function featureName(): string
    {
        return __('DNS validations');
    }

    protected function columns(): array
    {
        return ['zone_id', 'record_id', 'status', 'resolver', 'checked_at'];
    }
}
