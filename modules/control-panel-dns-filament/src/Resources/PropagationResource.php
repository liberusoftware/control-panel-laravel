<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Liberu\ControlPanel\Dns\Models\PropagationCheck;
use Liberu\ControlPanel\DnsFilament\Resources\PropagationResource\Pages\ListPropagationChecks;

final class PropagationResource extends DnsFeatureResource
{
    protected static ?string $model = PropagationCheck::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'DNS';

    protected static function featureFields(): array
    {
        return ['zone_id', 'record_id', 'status', 'checked_at'];
    }

    public static function getPages(): array
    {
        return ['index' => ListPropagationChecks::route('/')];
    }
}
