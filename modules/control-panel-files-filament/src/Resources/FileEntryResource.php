<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Files\Actions\DeleteFile;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource\Pages\CreateFileEntry;
use Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource\Pages\EditFileEntry;
use Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource\Pages\ListFileEntries;

final class FileEntryResource extends Resource
{
    protected static ?string $model = FileEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document';

    protected static string|\UnitEnum|null $navigationGroup = 'Files & Access';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('owner_id')->required()->maxLength(255),
            TextInput::make('path')->required()->maxLength(2048),
            TextInput::make('disk')->required()->maxLength(80),
            TextInput::make('mime_type')->maxLength(160),
            TextInput::make('size_bytes')->numeric()->minValue(0),
            TextInput::make('checksum')->maxLength(255),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('path')->searchable()->sortable(), TextColumn::make('disk'), TextColumn::make('mime_type'), TextColumn::make('size_bytes')->numeric(), TextColumn::make('status')->badge(), TextColumn::make('scanned_at')->dateTime()])->recordActions([
            DeleteAction::make()
                ->visible(fn (FileEntry $record): bool => $record->team_id === auth()->user()?->current_team_id && $record->status->value !== 'retained')
                ->action(fn (FileEntry $record): FileEntry => app(DeleteFile::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListFileEntries::route('/'), 'create' => CreateFileEntry::route('/create'), 'edit' => EditFileEntry::route('/{record}/edit')];
    }
}
