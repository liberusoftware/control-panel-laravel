<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\MonitoringFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Monitoring\Models\AlertRule;
use Liberu\ControlPanel\MonitoringFilament\Resources\AlertRuleResource\Pages\ListAlertRules;

final class AlertRuleResource extends MonitoringAssetResource
{
    protected static ?string $model = AlertRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('condition')->limit(80),
            TextColumn::make('threshold')->numeric(),
            IconColumn::make('active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAlertRules::route('/')];
    }
}
