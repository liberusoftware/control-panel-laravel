<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\BackupsFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Backups\Models\OffsiteTransfer;
use Liberu\ControlPanel\BackupsFilament\Resources\OffsiteTransferResource\Pages\ListOffsiteTransfers;

final class OffsiteTransferResource extends BackupFeatureResource
{
    protected static ?string $model = OffsiteTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('snapshot_id')->label('Snapshot')->searchable(),
            TextColumn::make('destination_id')->label('Destination')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('attempts')->numeric(),
            TextColumn::make('transferred_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListOffsiteTransfers::route('/')];
    }
}
