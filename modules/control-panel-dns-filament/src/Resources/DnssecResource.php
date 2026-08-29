<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Liberu\ControlPanel\Dns\Models\DnssecKey;
use Liberu\ControlPanel\DnsFilament\Resources\DnssecResource\Pages\ListDnssecKeys;

final class DnssecResource extends DnsFeatureResource
{
    protected static ?string $model = DnssecKey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    protected static function featureFields(): array
    {
        return ['key_tag', 'algorithm', 'digest_type', 'digest', 'public_key', 'active', 'rotated_at'];
    }

    public static function getPages(): array
    {
        return ['index' => ListDnssecKeys::route('/')];
    }
}
