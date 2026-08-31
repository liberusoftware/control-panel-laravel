<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ApiAutomation\Models\OrchestrationRun;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\OrchestrationRunResource\Pages\ListOrchestrationRuns;

final class OrchestrationRunResource extends Resource
{
    protected static ?string $model = OrchestrationRun::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-command-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Automation & Integrations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('template_id')->label('Template')->searchable(),
            TextColumn::make('schedule_id')->label('Schedule')->toggleable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('started_at')->dateTime()->sortable(),
            TextColumn::make('finished_at')->dateTime()->sortable(),
        ])->defaultSort('started_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListOrchestrationRuns::route('/')];
    }
}
