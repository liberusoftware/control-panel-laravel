<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesIngress;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesIngressResource\Pages\ListKubernetesIngresses;

final class KubernetesIngressResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesIngress::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('host')->searchable(),
            TextColumn::make('namespace'),
            TextColumn::make('status')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesIngresses::route('/')];
    }
}
