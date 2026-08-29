<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Files\Models\FilePermission;
use Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource\Pages\CreateFilePermission;
use Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource\Pages\EditFilePermission;
use Liberu\ControlPanel\FilesFilament\Resources\FilePermissionResource\Pages\ListFilePermissions;

final class FilePermissionResource extends Resource
{
    protected static ?string $model = FilePermission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('file_id')->uuid(), TextInput::make('home_directory_id')->uuid(), TextInput::make('subject_id')->required()->maxLength(255), TextInput::make('subject_type')->maxLength(100), TextInput::make('mode')->required()->numeric()->minValue(0)->maxValue(777), Toggle::make('recursive')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('subject_id')->searchable(), TextColumn::make('subject_type'), TextColumn::make('mode'), TextColumn::make('recursive')->boolean()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListFilePermissions::route('/'), 'create' => CreateFilePermission::route('/create'), 'edit' => EditFilePermission::route('/{record}/edit')];
    }
}
