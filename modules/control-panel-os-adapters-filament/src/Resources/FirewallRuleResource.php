<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\OsAdaptersFilament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\OsAdapters\Actions\DeleteFirewallRule;
use Liberu\ControlPanel\OsAdapters\Models\FirewallRule;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource\Pages\CreateFirewallRule;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource\Pages\EditFirewallRule;
use Liberu\ControlPanel\OsAdaptersFilament\Resources\FirewallRuleResource\Pages\ListFirewallRules;

final class FirewallRuleResource extends Resource
{
    protected static ?string $model = FirewallRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Server Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('node_id')->required()->maxLength(255),
            TextInput::make('direction')->required()->maxLength(20),
            TextInput::make('action')->required()->maxLength(20),
            TextInput::make('protocol')->maxLength(20),
            TextInput::make('port')->numeric()->minValue(1)->maxValue(65535),
            TextInput::make('source')->maxLength(64),
            TextInput::make('comment')->maxLength(255),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('direction')->badge(),
            TextColumn::make('action')->badge(),
            TextColumn::make('protocol'),
            TextColumn::make('port'),
            TextColumn::make('source'),
            TextColumn::make('active')->badge(),
        ])->recordActions([
            Action::make('delete')
                ->requiresConfirmation()
                ->color('danger')
                ->action(fn (FirewallRule $record) => app(DeleteFirewallRule::class)->execute($record)),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListFirewallRules::route('/'), 'create' => CreateFirewallRule::route('/create'), 'edit' => EditFirewallRule::route('/{record}/edit')];
    }
}
