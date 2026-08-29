<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Liberu\ControlPanel\Dns\Models\DnsValidation;
use Liberu\ControlPanel\DnsFilament\Resources\DnsValidationResource\Pages\ListDnsValidations;

final class DnsValidationResource extends DnsFeatureResource
{
    protected static ?string $model = DnsValidation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    protected static function featureFields(): array
    {
        return ['zone_id', 'record_id', 'status', 'resolver', 'checked_at'];
    }

    public static function getPages(): array
    {
        return ['index' => ListDnsValidations::route('/')];
    }
}
