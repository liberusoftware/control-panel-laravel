<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesAutoscaler;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesAutoscalerResource\Pages\ListKubernetesAutoscalers;

final class KubernetesAutoscalerResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesAutoscaler::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('target')->searchable(),
            TextColumn::make('min_replicas')->numeric(),
            TextColumn::make('max_replicas')->numeric(),
            TextColumn::make('metric'),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesAutoscalers::route('/')];
    }
}
