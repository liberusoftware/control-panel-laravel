<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesWorkload;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesWorkloadResource\Pages\ListKubernetesWorkloads;

final class KubernetesWorkloadResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesWorkload::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('kind')->badge(),
            TextColumn::make('namespace'),
            TextColumn::make('replicas')->numeric(),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesWorkloads::route('/')];
    }
}
