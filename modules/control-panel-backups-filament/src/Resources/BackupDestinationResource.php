<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Backups\Actions\DeleteDestination;
use Liberu\ControlPanel\Backups\Models\BackupDestination;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource\Pages\CreateBackupDestination;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource\Pages\EditBackupDestination;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupDestinationResource\Pages\ListBackupDestinations;

final class BackupDestinationResource extends Resource
{
    protected static ?string $model = BackupDestination::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('driver')->required()->maxLength(40),
            TextInput::make('retention_days')->required()->numeric()->minValue(1),
            Toggle::make('default')->default(false),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('driver')->badge(), TextColumn::make('retention_days'), TextColumn::make('default')->badge(), TextColumn::make('active')->badge(), TextColumn::make('last_checked_at')->dateTime()->toggleable()])->recordActions([
            DeleteAction::make()->action(function (BackupDestination $record): void {
                abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                app(DeleteDestination::class)->execute($record);
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupDestinations::route('/'), 'create' => CreateBackupDestination::route('/create'), 'edit' => EditBackupDestination::route('/{record}/edit')];
    }
}
