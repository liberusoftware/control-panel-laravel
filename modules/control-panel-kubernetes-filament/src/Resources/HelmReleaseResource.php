<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Kubernetes\Actions\DeleteHelmRelease;
use Liberu\ControlPanel\Kubernetes\Models\HelmRelease;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource\Pages\CreateHelmRelease;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource\Pages\EditHelmRelease;
use Liberu\ControlPanel\KubernetesFilament\Resources\HelmReleaseResource\Pages\ListHelmReleases;
use Liberu\ControlPanel\KubernetesFilament\Widgets\HelmStatsWidget;

final class HelmReleaseResource extends Resource
{
    protected static ?string $model = HelmRelease::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('cluster_id')->label('Cluster ID')->uuid()->maxLength(36),
            TextInput::make('namespace')->required()->maxLength(255)->default('default'),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('chart')->required()->maxLength(255),
            TextInput::make('version')->maxLength(80),
            Select::make('status')->options(['pending' => 'Pending', 'deployed' => 'Deployed', 'failed' => 'Failed', 'uninstalled' => 'Uninstalled'])->required()->default('pending'),
            KeyValue::make('values')->label('Helm values')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('chart')->searchable()->sortable(),
            TextColumn::make('namespace')->badge(),
            TextColumn::make('version'),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->recordActions([
            Action::make('delete')->requiresConfirmation()->color('danger')->action(fn (HelmRelease $record) => app(DeleteHelmRelease::class)->execute($record)),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListHelmReleases::route('/'), 'create' => CreateHelmRelease::route('/create'), 'edit' => EditHelmRelease::route('/{record}/edit')];
    }

    public static function getWidgets(): array
    {
        return [HelmStatsWidget::class];
    }
}
