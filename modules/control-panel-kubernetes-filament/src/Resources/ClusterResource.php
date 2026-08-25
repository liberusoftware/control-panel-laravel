<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Kubernetes\Actions\ArchiveCluster;
use Liberu\ControlPanel\Kubernetes\Actions\SuspendCluster;
use Liberu\ControlPanel\Kubernetes\Models\Cluster;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages\CreateCluster;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages\EditCluster;
use Liberu\ControlPanel\KubernetesFilament\Resources\ClusterResource\Pages\ListClusters;

final class ClusterResource extends Resource
{
    protected static ?string $model = Cluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('endpoint')->required()->url()->maxLength(2048),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('configuration')->label('Cluster configuration'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('endpoint')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->recordActions([
            Action::make('suspend')->requiresConfirmation()->visible(fn (Cluster $record): bool => $record->status !== 'suspended' && $record->status !== 'archived')->action(fn (Cluster $record): Cluster => app(SuspendCluster::class)->execute($record)),
            Action::make('archive')->requiresConfirmation()->visible(fn (Cluster $record): bool => $record->status !== 'archived')->action(fn (Cluster $record): Cluster => app(ArchiveCluster::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListClusters::route('/'), 'create' => CreateCluster::route('/create'), 'edit' => EditCluster::route('/{record}/edit')];
    }
}
