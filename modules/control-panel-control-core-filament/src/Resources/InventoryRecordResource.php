<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ControlCore\Models\InventoryRecord;

final class InventoryRecordResource extends Resource
{
    protected static ?string $model = InventoryRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255),
            TextInput::make('kind')->required()->maxLength(120),
            TextInput::make('record_key')->required()->maxLength(255),
            KeyValue::make('value'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('kind')->searchable(), TextColumn::make('record_key')->searchable(), TextColumn::make('node_id'), TextColumn::make('observed_at')->dateTime()->sortable()])->defaultSort('observed_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventoryRecords::route('/')];
    }
}
