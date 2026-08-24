<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Files\Models\FileEntry;
use Liberu\ControlPanel\FilesFilament\Resources\FileEntryResource\Pages\ListFileEntries;

final class FileEntryResource extends Resource
{
    protected static ?string $model = FileEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('path')->searchable()->sortable(), TextColumn::make('disk'), TextColumn::make('mime_type'), TextColumn::make('size_bytes')->numeric(), TextColumn::make('status')->badge(), TextColumn::make('scanned_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListFileEntries::route('/')];
    }
}
