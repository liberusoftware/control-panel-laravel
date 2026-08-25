<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\DatabasesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Databases\Models\DatabaseBackup;
use Liberu\ControlPanel\DatabasesFilament\Resources\DatabaseBackupResource\Pages\ListDatabaseBackups;

final class DatabaseBackupResource extends Resource
{
    protected static ?string $model = DatabaseBackup::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('database.name')->label('Database')->sortable(), TextColumn::make('type')->badge(),
            TextColumn::make('destination')->limit(30), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListDatabaseBackups::route('/')];
    }
}
