<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ControlCore\Models\OperationLock;

final class OperationLockResource extends Resource
{
    protected static ?string $model = OperationLock::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255),
            TextInput::make('operation_key')->required()->maxLength(255),
            TextInput::make('owner')->required()->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('operation_key')->searchable(), TextColumn::make('owner'), TextColumn::make('node_id'), TextColumn::make('expires_at')->dateTime()->sortable()])->defaultSort('expires_at');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOperationLocks::route('/')];
    }
}
