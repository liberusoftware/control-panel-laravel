<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Databases\Models\DatabaseEngine;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseEngineResource\Pages\ListDatabaseEngines;

final class DatabaseEngineResource extends Resource
{
    protected static ?string $model = DatabaseEngine::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(), TextColumn::make('driver'),
            TextColumn::make('version'), TextColumn::make('host'), TextColumn::make('active')->badge(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where(fn (Builder $query) => $query->where('team_id', auth()->user()?->current_team_id)->orWhereNull('team_id'));
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabaseEngines::route('/')];
    }
}
