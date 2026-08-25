<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\ApiAutomationFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\ApiAutomation\Models\WebhookEndpoint;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages\CreateWebhookEndpoint;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages\EditWebhookEndpoint;
use Liberu\ControlPanel\ApiAutomationFilament\Resources\WebhookEndpointResource\Pages\ListWebhookEndpoints;

final class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(160),
            TextInput::make('url')->required()->url()->maxLength(2048),
            KeyValue::make('events')->label('Subscribed events'),
            TextInput::make('secret')->password()->revealable()->maxLength(255),
            TextInput::make('retry_limit')->numeric()->minValue(0)->maxValue(20)->default(5),
            Select::make('status')->options(['active' => 'Active', 'paused' => 'Paused', 'failed' => 'Failed'])->required()->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('url')->limit(40), TextColumn::make('status')->badge(), TextColumn::make('failure_count'), TextColumn::make('created_at')->dateTime()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListWebhookEndpoints::route('/'), 'create' => CreateWebhookEndpoint::route('/create'), 'edit' => EditWebhookEndpoint::route('/{record}/edit')];
    }
}
