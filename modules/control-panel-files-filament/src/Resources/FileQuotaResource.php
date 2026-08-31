<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\FilesFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Files\Models\FileQuota;
use Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource\Pages\CreateFileQuota;
use Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource\Pages\EditFileQuota;
use Liberu\ControlPanel\FilesFilament\Resources\FileQuotaResource\Pages\ListFileQuotas;

final class FileQuotaResource extends Resource
{
    protected static ?string $model = FileQuota::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Files & Access';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('owner_id')->maxLength(255),
            TextInput::make('limit_bytes')->required()->numeric()->minValue(0),
            TextInput::make('used_bytes')->required()->numeric()->minValue(0),
            TextInput::make('files_count')->required()->numeric()->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('owner_id')->searchable(), TextColumn::make('limit_bytes')->numeric(), TextColumn::make('used_bytes')->numeric(), TextColumn::make('files_count')->numeric()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListFileQuotas::route('/'), 'create' => CreateFileQuota::route('/create'), 'edit' => EditFileQuota::route('/{record}/edit')];
    }
}
