<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ModuleResource\Pages;
use App\Models\Module;
use App\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Modules';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->disabled(),
            TextInput::make('version')->disabled(),
            Textarea::make('description')->disabled(),
            Toggle::make('enabled')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('version')->sortable(),
                TextColumn::make('description')->limit(50),
                IconColumn::make('enabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('dependencies')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : ($state ?? ''))
                    ->limit(30),
            ])
            ->filters([
                TernaryFilter::make('enabled')
                    ->label('Status')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled')
                    ->queries(
                        true: fn (Builder $query) => $query->where('enabled', true),
                        false: fn (Builder $query) => $query->where('enabled', false),
                    ),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn ($record) => $record->enabled ? 'Disable' : 'Enable')
                    ->icon(fn ($record) => $record->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->enabled ? 'danger' : 'success')
                    ->action(function ($record) {
                        $manager = app(ModuleManager::class);
                        $record->enabled
                            ? $manager->disable($record->name)
                            : $manager->enable($record->name);
                    })
                    ->requiresConfirmation(),
                Action::make('install')
                    ->label('Install')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn ($record) => app(ModuleManager::class)->install($record->name))
                    ->visible(fn ($record) => !$record->enabled)
                    ->requiresConfirmation(),
                Action::make('uninstall')
                    ->label('Uninstall')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(fn ($record) => app(ModuleManager::class)->uninstall($record->name))
                    ->visible(fn ($record) => $record->enabled)
                    ->requiresConfirmation(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label('Enable Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $manager = app(ModuleManager::class);
                            foreach ($records as $record) {
                                $manager->enable($record->name);
                            }
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('disable')
                        ->label('Disable Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $manager = app(ModuleManager::class);
                            foreach ($records as $record) {
                                $manager->disable($record->name);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModules::route('/'),
            'view' => Pages\ViewModule::route('/{record}'),
        ];
    }
}
