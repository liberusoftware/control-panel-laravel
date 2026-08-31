<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Databases\Models\DatabaseHealthCheck;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseHealthCheckResource\Pages\ListDatabaseHealthChecks;

final class DatabaseHealthCheckResource extends DatabaseFeatureResource
{
    protected static ?string $model = DatabaseHealthCheck::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|\UnitEnum|null $navigationGroup = 'Databases';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('database_id')->label('Database')->searchable(),
            IconColumn::make('healthy')->boolean(),
            TextColumn::make('latency_ms')->label('Latency (ms)')->numeric(),
            TextColumn::make('message')->limit(80),
            TextColumn::make('checked_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabaseHealthChecks::route('/')];
    }
}
