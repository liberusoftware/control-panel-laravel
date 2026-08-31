<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\OsAdapters\Models\SupportMatrixEntry;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\SupportMatrixResource\Pages\CreateSupportMatrix;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\SupportMatrixResource\Pages\EditSupportMatrix;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\SupportMatrixResource\Pages\ListSupportMatrix;

final class SupportMatrixResource extends Resource
{
    protected static ?string $model = SupportMatrixEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Server Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('operating_system')->required()->maxLength(80),
            TextInput::make('version')->required()->maxLength(80),
            TextInput::make('capability')->required()->maxLength(120),
            Toggle::make('supported')->default(false),
            TextInput::make('minimum_adapter_version')->maxLength(80),
            Textarea::make('notes')->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('operating_system')->searchable()->sortable(), TextColumn::make('version'), TextColumn::make('capability')->searchable(), TextColumn::make('supported')->boolean(), TextColumn::make('minimum_adapter_version')])->defaultSort('operating_system');
    }

    public static function getPages(): array
    {
        return ['index' => ListSupportMatrix::route('/'), 'create' => CreateSupportMatrix::route('/create'), 'edit' => EditSupportMatrix::route('/{record}/edit')];
    }
}
