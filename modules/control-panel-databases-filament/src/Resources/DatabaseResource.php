<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Databases\Models\Database;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseResource\Pages\ListDatabases;

final class DatabaseResource extends Resource
{
    protected static ?string $model = Database::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('engine.name')->label('Engine')->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('charset'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabases::route('/')];
    }
}
