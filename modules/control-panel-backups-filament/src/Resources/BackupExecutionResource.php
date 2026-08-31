<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Backups\Models\BackupExecution;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupExecutionResource\Pages\ListBackupExecutions;

final class BackupExecutionResource extends Resource
{
    protected static ?string $model = BackupExecution::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('policy_id')->label('Policy')->searchable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('consistency')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('started_at')->dateTime()->sortable(),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ])->defaultSort('started_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupExecutions::route('/')];
    }
}
