<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\SecurityFilament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\Security\Models\MfaRbacPolicy;
use Liberu\ControlPanel\SecurityFilament\Resources\MfaRbacPolicyResource\Pages\ListMfaRbacPolicies;

final class MfaRbacPolicyResource extends Resource
{
    protected static ?string $model = MfaRbacPolicy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Security & Compliance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('subject_type')->searchable(),
            TextColumn::make('subject_id')->searchable(),
            TextColumn::make('mfa_required')->label('MFA')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListMfaRbacPolicies::route('/')];
    }
}
