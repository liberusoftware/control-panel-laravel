<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesClusterView;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesClusterViewResource\Pages\ListKubernetesClusterViews;

final class KubernetesClusterViewResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesClusterView::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesClusterViews::route('/')];
    }
}
