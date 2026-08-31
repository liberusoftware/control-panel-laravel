<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource\Pages;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Liberu\ControlPanel\KubernetesFilament\Resources\KubernetesNodeResource;

final class ViewKubernetesNode extends ViewRecord
{
    protected static string $resource = KubernetesNodeResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Node information')->schema([
                TextEntry::make('name'),
                TextEntry::make('uid'),
                TextEntry::make('status')->badge(),
                IconEntry::make('schedulable')->boolean(),
                TextEntry::make('getRole')->label('Role')->state(fn ($record): string => $record->getRole()),
            ])->columns(3),
            Section::make('System information')->schema([
                TextEntry::make('kubernetes_version'),
                TextEntry::make('container_runtime'),
                TextEntry::make('os_image'),
                TextEntry::make('kernel_version'),
                TextEntry::make('architecture'),
                TextEntry::make('status_message'),
            ])->columns(2),
            Section::make('Resources')->schema([
                TextEntry::make('getCpuCapacity')->label('CPU capacity')->state(fn ($record): ?string => $record->getCpuCapacity() === null ? null : number_format($record->getCpuCapacity(), 2).' cores'),
                TextEntry::make('getAllocatableCpu')->label('CPU allocatable')->state(fn ($record): ?string => $record->getAllocatableCpu() === null ? null : number_format($record->getAllocatableCpu(), 2).' cores'),
                TextEntry::make('getMemoryCapacity')->label('Memory capacity')->state(fn ($record): ?string => $record->getMemoryCapacity() === null ? null : number_format($record->getMemoryCapacity(), 2).' GB'),
                TextEntry::make('getAllocatableMemory')->label('Memory allocatable')->state(fn ($record): ?string => $record->getAllocatableMemory() === null ? null : number_format($record->getAllocatableMemory(), 2).' GB'),
            ])->columns(2),
            Section::make('Labels and taints')->schema([
                KeyValueEntry::make('labels'),
                KeyValueEntry::make('taints'),
            ])->columns(2),
        ]);
    }
}
