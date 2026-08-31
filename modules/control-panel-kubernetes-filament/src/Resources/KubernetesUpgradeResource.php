<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesUpgrade;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesUpgradeResource\Pages\ListKubernetesUpgrades;

final class KubernetesUpgradeResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesUpgrade::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('cluster_id')->label('Cluster')->searchable(),
            TextColumn::make('from_version'),
            TextColumn::make('to_version'),
            TextColumn::make('status')->badge(),
            TextColumn::make('started_at')->dateTime()->sortable(),
            TextColumn::make('completed_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesUpgrades::route('/')];
    }
}
