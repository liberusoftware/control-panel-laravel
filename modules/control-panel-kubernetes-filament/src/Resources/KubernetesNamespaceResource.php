<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNamespace;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNamespaceResource\Pages\ListKubernetesNamespaces;

final class KubernetesNamespaceResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesNamespace::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('cluster_id')->label('Cluster')->searchable(),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesNamespaces::route('/')];
    }
}
