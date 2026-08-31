<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Files\Models\HomeDirectory;
use Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource\Pages\CreateHomeDirectory;
use Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource\Pages\EditHomeDirectory;
use Liberu\ControlPanel\FilesFilament\Resources\HomeDirectoryResource\Pages\ListHomeDirectories;

final class HomeDirectoryResource extends Resource
{
    protected static ?string $model = HomeDirectory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|\UnitEnum|null $navigationGroup = 'Files & Access';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('owner_id')->maxLength(255), TextInput::make('path')->required()->maxLength(2048), TextInput::make('disk')->required()->maxLength(100), TextInput::make('mode')->numeric()->minValue(0)->maxValue(777), KeyValue::make('metadata')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('owner_id')->searchable(), TextColumn::make('path')->searchable()->sortable(), TextColumn::make('disk'), TextColumn::make('mode'), TextColumn::make('status')->badge()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListHomeDirectories::route('/'), 'create' => CreateHomeDirectory::route('/create'), 'edit' => EditHomeDirectory::route('/{record}/edit')];
    }
}
