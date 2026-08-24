<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages\ListClusters;

final class ClusterResource extends Resource
{
    protected static ?string $model = Cluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('endpoint')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListClusters::route('/')];
    }
}
