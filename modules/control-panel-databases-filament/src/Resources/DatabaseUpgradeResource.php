<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Databases\Models\DatabaseUpgrade;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseUpgradeResource\Pages\ListDatabaseUpgrades;

final class DatabaseUpgradeResource extends DatabaseFeatureResource
{
    protected static ?string $model = DatabaseUpgrade::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Databases';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('database_id')->label('Database')->searchable(),
            TextColumn::make('from_version'),
            TextColumn::make('to_version'),
            TextColumn::make('status')->badge(),
            TextColumn::make('started_at')->dateTime()->sortable(),
            TextColumn::make('finished_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabaseUpgrades::route('/')];
    }
}
