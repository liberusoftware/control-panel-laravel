<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\ControlPanel\WebHosting\Models\GitDeployment;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource\Pages\CreateGitDeployment;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource\Pages\EditGitDeployment;
use Liberu\ControlPanel\WebHostingFilament\Resources\GitDeploymentResource\Pages\ListGitDeployments;

final class GitDeploymentResource extends Resource
{
    protected static ?string $model = GitDeployment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain_id')->required()->uuid(),
            TextInput::make('repository_url')->required()->url()->maxLength(2048),
            TextInput::make('repository_type')->required()->maxLength(40),
            TextInput::make('branch')->required()->default('main')->maxLength(255),
            TextInput::make('deploy_path')->required()->maxLength(1024),
            TextInput::make('build_command')->maxLength(1024),
            TextInput::make('deploy_command')->maxLength(1024),
            Toggle::make('auto_deploy')->default(false),
            KeyValue::make('metadata'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('repository_name')->label('Repository')->searchable(),
            TextColumn::make('repository_type')->badge(), TextColumn::make('branch'),
            TextColumn::make('status')->badge(), TextColumn::make('auto_deploy')->badge(),
            TextColumn::make('last_deployed_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', auth()->user()?->current_team_id);
    }

    public static function getPages(): array
    {
        return ['index' => ListGitDeployments::route('/'), 'create' => CreateGitDeployment::route('/create'), 'edit' => EditGitDeployment::route('/{record}/edit')];
    }
}
