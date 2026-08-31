<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Backups\Models\BackupRestore;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupRestoreResource\Pages\ListBackupRestores;

final class BackupRestoreResource extends BackupFeatureResource
{
    protected static ?string $model = BackupRestore::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('snapshot_id')->label('Snapshot')->searchable(),
            TextColumn::make('target')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('started_at')->dateTime()->sortable(),
            TextColumn::make('finished_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupRestores::route('/')];
    }
}
