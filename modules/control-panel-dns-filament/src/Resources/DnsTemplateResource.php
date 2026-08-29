<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DnsFilament\Resources;

use Liberu\ControlPanel\Dns\Models\DnsTemplate;
use Liberu\ControlPanel\DnsFilament\Resources\DnsTemplateResource\Pages\ListDnsTemplates;

final class DnsTemplateResource extends DnsFeatureResource
{
    protected static ?string $model = DnsTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    protected static function featureFields(): array
    {
        return ['name', 'active'];
    }

    public static function getPages(): array
    {
        return ['index' => ListDnsTemplates::route('/')];
    }
}
