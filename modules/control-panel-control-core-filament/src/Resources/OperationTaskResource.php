<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ControlCoreFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ControlCore\Actions\CancelOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\RetryOperationTask;
use Liberu\ControlPanel\ControlCore\Actions\TransitionOperationTask;
use Liberu\ControlPanel\ControlCore\Enums\TaskStatus;
use Liberu\ControlPanel\ControlCore\Models\OperationTask;

final class OperationTaskResource extends Resource
{
    protected static ?string $model = OperationTask::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->maxLength(255),
            TextInput::make('operation')->required()->maxLength(255),
            TextInput::make('idempotency_key')->maxLength(255),
            TextInput::make('status')->required()->maxLength(40),
            KeyValue::make('payload'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('operation')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('steps_count')->label('Steps'), TextColumn::make('attempts'), TextColumn::make('timeout_at')->dateTime(), TextColumn::make('compensation_status')->badge(), TextColumn::make('finished_at')->dateTime()])
            ->recordActions([
                Action::make('transition')
                    ->form([Select::make('status')->options([
                        'running' => 'Running', 'succeeded' => 'Succeeded', 'failed' => 'Failed', 'cancelled' => 'Cancelled',
                    ])->required()])
                    ->visible(fn (OperationTask $record): bool => ! in_array($record->status, [TaskStatus::Succeeded, TaskStatus::Failed, TaskStatus::Cancelled], true))
                    ->action(fn (OperationTask $record, array $data): OperationTask => app(TransitionOperationTask::class)->execute($record, TaskStatus::from($data['status']))),
                Action::make('retry')
                    ->requiresConfirmation()
                    ->visible(fn (OperationTask $record): bool => $record->status === TaskStatus::Failed)
                    ->action(fn (OperationTask $record): OperationTask => app(RetryOperationTask::class)->execute($record)),
                Action::make('cancel')
                    ->requiresConfirmation()
                    ->visible(fn (OperationTask $record): bool => in_array($record->status, [TaskStatus::Pending, TaskStatus::Running], true))
                    ->action(fn (OperationTask $record): OperationTask => app(CancelOperationTask::class)->execute($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('steps')->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOperationTasks::route('/')];
    }
}
