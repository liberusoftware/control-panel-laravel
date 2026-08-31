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
use Liberu\ControlPanel\Backups\Actions\DeleteSchedule;
use Liberu\ControlPanel\Backups\Models\BackupSchedule;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource\Pages\CreateBackupSchedule;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource\Pages\EditBackupSchedule;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupScheduleResource\Pages\ListBackupSchedules;

final class BackupScheduleResource extends Resource
{
    protected static ?string $model = BackupSchedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('policy_id')->required()->uuid(),
            TextInput::make('cron')->required()->maxLength(120),
            TextInput::make('timezone')->required()->maxLength(80)->default('UTC'),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('policy.name')->label('Policy'), TextColumn::make('cron'), TextColumn::make('timezone'), TextColumn::make('active')->badge(), TextColumn::make('next_run_at')->dateTime()])->recordActions([
            DeleteAction::make()->action(function (BackupSchedule $record): void {
                abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                app(DeleteSchedule::class)->execute($record);
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupSchedules::route('/'), 'create' => CreateBackupSchedule::route('/create'), 'edit' => EditBackupSchedule::route('/{record}/edit')];
    }
}
