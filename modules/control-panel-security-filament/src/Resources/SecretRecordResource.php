<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Models\SecretRecord;
use Liberu\ControlPanel\SecurityFilament\Resources\SecretRecordResource\Pages\ListSecretRecords;

final class SecretRecordResource extends Resource
{
    protected static ?string $model = SecretRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'Security & Compliance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('purpose')->searchable(),
            TextColumn::make('version'),
            TextColumn::make('status')->badge(),
            TextColumn::make('expires_at')->dateTime()->sortable(),
            TextColumn::make('rotated_at')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListSecretRecords::route('/')];
    }
}
