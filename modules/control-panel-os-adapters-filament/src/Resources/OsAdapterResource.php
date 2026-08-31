<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\OsAdapters\Models\OsAdapter;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource\Pages\CreateOsAdapter;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource\Pages\EditOsAdapter;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\OsAdapterResource\Pages\ListOsAdapters;

final class OsAdapterResource extends Resource
{
    protected static ?string $model = OsAdapter::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Server Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255),
            TextInput::make('operating_system')->required()->maxLength(120),
            TextInput::make('version')->required()->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('capabilities'),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('operating_system')->searchable()->sortable(), TextColumn::make('version'), TextColumn::make('status')->badge(), TextColumn::make('node_id'), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListOsAdapters::route('/'), 'create' => CreateOsAdapter::route('/create'), 'edit' => EditOsAdapter::route('/{record}/edit')];
    }
}
