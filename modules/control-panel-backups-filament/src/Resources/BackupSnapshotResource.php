<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Backups\Models\BackupSnapshot;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource\Pages\CreateBackupSnapshot;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource\Pages\EditBackupSnapshot;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupSnapshotResource\Pages\ListBackupSnapshots;

final class BackupSnapshotResource extends Resource
{
    protected static ?string $model = BackupSnapshot::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('policy_id')->required()->uuid(),
            TextInput::make('location')->required()->maxLength(2048),
            Select::make('status')->options(['queued' => 'Queued', 'running' => 'Running', 'verified' => 'Verified', 'failed' => 'Failed', 'restored' => 'Restored'])->required(),
            TextInput::make('size_bytes')->numeric()->minValue(0),
            TextInput::make('checksum')->maxLength(255),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('location')->searchable(), TextColumn::make('policy.name')->label('Policy'), TextColumn::make('status')->badge(), TextColumn::make('size_bytes')->numeric(), TextColumn::make('verified_at')->dateTime(), TextColumn::make('created_at')->dateTime()->sortable()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupSnapshots::route('/'), 'create' => CreateBackupSnapshot::route('/create'), 'edit' => EditBackupSnapshot::route('/{record}/edit')];
    }
}
