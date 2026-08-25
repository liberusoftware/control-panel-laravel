<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\CreateKubernetesNode;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\EditKubernetesNode;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\ListKubernetesNodes;

final class KubernetesNodeResource extends Resource
{
    protected static ?string $model = KubernetesNode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('cluster_id')->required()->uuid(),
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('kubernetes_version')->maxLength(80),
            TextInput::make('container_runtime')->maxLength(120),
            TextInput::make('os_image')->maxLength(255),
            TextInput::make('architecture')->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
            Toggle::make('schedulable')->default(true),
            KeyValue::make('labels'),
            KeyValue::make('taints'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('kubernetes_version'), TextColumn::make('status')->badge(), TextColumn::make('schedulable')->badge(), TextColumn::make('last_heartbeat_at')->dateTime()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesNodes::route('/'), 'create' => CreateKubernetesNode::route('/create'), 'edit' => EditKubernetesNode::route('/{record}/edit')];
    }
}
