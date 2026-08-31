<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesStorageClaim;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesStorageClaimResource\Pages\ListKubernetesStorageClaims;

final class KubernetesStorageClaimResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesStorageClaim::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('storage_class'),
            TextColumn::make('capacity_bytes')->numeric(),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesStorageClaims::route('/')];
    }
}
