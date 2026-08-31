<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesRbacBinding;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesRbacBindingResource\Pages\ListKubernetesRbacBindings;

final class KubernetesRbacBindingResource extends KubernetesAssetResource
{
    protected static ?string $model = KubernetesRbacBinding::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('role')->badge(),
            TextColumn::make('namespace'),
            IconColumn::make('active')->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesRbacBindings::route('/')];
    }
}
