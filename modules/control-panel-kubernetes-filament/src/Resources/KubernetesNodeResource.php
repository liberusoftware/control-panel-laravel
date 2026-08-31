<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Kubernetes\Actions\CordonNode;
use Liberu\ControlPanel\Kubernetes\Actions\DrainNode;
use Liberu\ControlPanel\Kubernetes\Actions\LabelNode;
use Liberu\ControlPanel\Kubernetes\Actions\UncordonNode;
use Liberu\ControlPanel\Kubernetes\Actions\UnlabelNode;
use Liberu\ControlPanel\Kubernetes\Models\KubernetesNode;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\CreateKubernetesNode;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\EditKubernetesNode;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\ListKubernetesNodes;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages\ViewKubernetesNode;

final class KubernetesNodeResource extends Resource
{
    protected static ?string $model = KubernetesNode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static string|\UnitEnum|null $navigationGroup = 'Kubernetes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('cluster_id')->required()->uuid(),
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('kubernetes_version')->maxLength(80),
            TextInput::make('container_runtime')->maxLength(120),
            TextInput::make('os_image')->maxLength(255),
            TextInput::make('kernel_version')->maxLength(120),
            TextInput::make('architecture')->maxLength(80),
            TextInput::make('status')->required()->maxLength(40),
            Toggle::make('schedulable')->default(true),
            KeyValue::make('labels'),
            KeyValue::make('taints'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('name')->searchable(), TextColumn::make('kubernetes_version'), TextColumn::make('kernel_version')->toggleable(), TextColumn::make('status')->badge(), TextColumn::make('schedulable')->badge(), TextColumn::make('last_heartbeat_at')->dateTime()])
            ->recordActions([
                ViewAction::make(),
                Action::make('cordon')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (KubernetesNode $record): bool => $record->schedulable)
                    ->action(fn (KubernetesNode $record): KubernetesNode => app(CordonNode::class)->execute($record)),
                Action::make('uncordon')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (KubernetesNode $record): bool => ! $record->schedulable)
                    ->action(fn (KubernetesNode $record): KubernetesNode => app(UncordonNode::class)->execute($record)),
                Action::make('drain')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (KubernetesNode $record): bool => $record->schedulable)
                    ->action(fn (KubernetesNode $record): KubernetesNode => app(DrainNode::class)->execute($record)),
                Action::make('label')
                    ->icon('heroicon-o-tag')
                    ->form([
                        TextInput::make('key')->required()->maxLength(253),
                        TextInput::make('value')->required()->maxLength(63),
                    ])
                    ->action(fn (KubernetesNode $record, array $data): KubernetesNode => app(LabelNode::class)->execute($record, $data['key'], $data['value'])),
                Action::make('unlabel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([TextInput::make('key')->required()->maxLength(253)])
                    ->action(fn (KubernetesNode $record, array $data): KubernetesNode => app(UnlabelNode::class)->execute($record, $data['key'])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListKubernetesNodes::route('/'), 'create' => CreateKubernetesNode::route('/create'), 'view' => ViewKubernetesNode::route('/{record}'), 'edit' => EditKubernetesNode::route('/{record}/edit')];
    }
}
