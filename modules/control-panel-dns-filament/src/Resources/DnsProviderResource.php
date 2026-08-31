<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Liberu\ControlPanel\Dns\Models\DnsProvider;
use Liberu\ControlPanel\DnsFilament\Resources\DnsProviderResource\Pages\ListDnsProviders;

final class DnsProviderResource extends DnsFeatureResource
{
    protected static ?string $model = DnsProvider::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud';

    protected static string|\UnitEnum|null $navigationGroup = 'DNS';

    protected static function featureFields(): array
    {
        return ['name', 'driver', 'endpoint', 'settings', 'active'];
    }

    public static function getPages(): array
    {
        return ['index' => ListDnsProviders::route('/')];
    }
}
