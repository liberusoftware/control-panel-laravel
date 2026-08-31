<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Backups\Models\BackupEncryption;
use Liberu\ControlPanel\BackupsFilament\Resources\BackupEncryptionResource\Pages\ListBackupEncryptions;

final class BackupEncryptionResource extends BackupFeatureResource
{
    protected static ?string $model = BackupEncryption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('policy_id')->label('Policy')->searchable(),
            TextColumn::make('algorithm')->badge(),
            IconColumn::make('active')->boolean(),
            TextColumn::make('rotated_at')->dateTime(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackupEncryptions::route('/')];
    }
}
