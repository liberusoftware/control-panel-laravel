<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Actions\ResolveSecurityFinding;
use Liberu\ControlPanel\Security\Models\SecurityFinding;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages\CreateSecurityFinding;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages\EditSecurityFinding;
use Liberu\ControlPanel\SecurityFilament\Resources\SecurityFindingResource\Pages\ListSecurityFindings;

final class SecurityFindingResource extends Resource
{
    protected static ?string $model = SecurityFinding::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|\UnitEnum|null $navigationGroup = 'Control Panel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('subject_type')->required()->maxLength(255),
            TextInput::make('subject_id')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(120),
            TextInput::make('severity')->required()->maxLength(40),
            TextInput::make('status')->disabled()->dehydrated(false),
            TextInput::make('summary')->required()->maxLength(1000),
            KeyValue::make('evidence'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('summary')->searchable(), TextColumn::make('severity')->badge(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()->sortable()])
            ->recordActions([
                Action::make('resolve')
                    ->requiresConfirmation()
                    ->visible(fn (SecurityFinding $record): bool => $record->status === 'open')
                    ->action(fn (SecurityFinding $record): SecurityFinding => app(ResolveSecurityFinding::class)->execute($record)),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListSecurityFindings::route('/'), 'create' => CreateSecurityFinding::route('/create'), 'edit' => EditSecurityFinding::route('/{record}/edit')];
    }
}
use Filament\Actions\Action;
