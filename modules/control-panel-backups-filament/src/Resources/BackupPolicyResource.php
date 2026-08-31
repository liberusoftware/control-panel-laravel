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
use Liberu\ControlPanel\Backups\Actions\DeletePolicy;
use Liberu\ControlPanel\Backups\Models\BackupPolicy;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages\CreateBackupPolicy;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages\EditBackupPolicy;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupPolicyResource\Pages\ListBackupPolicies;

final class BackupPolicyResource extends Resource
{
    protected static ?string $model = BackupPolicy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('storage_driver')->required()->maxLength(80),
            TextInput::make('retention_days')->required()->numeric()->minValue(1),
            Toggle::make('encrypted')->default(true),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('storage_driver'), TextColumn::make('retention_days'), TextColumn::make('encrypted')->badge(), TextColumn::make('active')->badge()])->recordActions([
            DeleteAction::make()->action(function (BackupPolicy $record): void {
                abort_if(auth()->user()?->current_team_id === null, 403, 'A current team is required.');
                abort_unless((string) $record->team_id === (string) auth()->user()?->current_team_id, 404);
                app(DeletePolicy::class)->execute($record);
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupPolicies::route('/'), 'create' => CreateBackupPolicy::route('/create'), 'edit' => EditBackupPolicy::route('/{record}/edit')];
    }
}
