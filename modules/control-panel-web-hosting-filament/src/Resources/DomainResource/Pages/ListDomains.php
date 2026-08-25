<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Liberu\ControlPanel\WebHosting\Actions\ActivateDomain;
use Liberu\ControlPanel\WebHosting\Actions\ArchiveDomain;
use Liberu\ControlPanel\WebHosting\Actions\SuspendDomain;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;
use Liberu\ControlPanel\WebHostingFilament\Resources\DomainResource;

final class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;

    protected function getTableActions(): array
    {
        return [
            Action::make('activate')->action(fn ($record) => app(ActivateDomain::class)->execute($record))->visible(fn ($record): bool => $record->status !== DomainStatus::Active && $record->status !== DomainStatus::Archived),
            Action::make('suspend')->requiresConfirmation()->form(['reason' => Textarea::make('reason')->required()->maxLength(1000)])->action(fn ($record, array $data) => app(SuspendDomain::class)->execute($record, $data['reason']))->visible(fn ($record): bool => $record->status !== DomainStatus::Suspended && $record->status !== DomainStatus::Archived),
            Action::make('archive')->requiresConfirmation()->action(fn ($record) => app(ArchiveDomain::class)->execute($record))->visible(fn ($record): bool => $record->status !== DomainStatus::Archived),
        ];
    }
}
