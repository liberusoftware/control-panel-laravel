<?php

use Filament\Schemas\Schema;
use Liberu\Foundation\IdentityFilament\Resources\UserResource;
use Liberu\Foundation\OrganizationsFilament\Resources\TeamResource;

it('builds every module resource form and page map', function () {
    foreach ([UserResource::class, TeamResource::class] as $resource) {
        $schema = $resource::form(Schema::make());

        expect($schema->getComponents())->not->toBeEmpty()
            ->and($resource::getPages())->toHaveKeys(['index', 'create', 'edit']);
    }
});

it('fails closed in every tenant-scoped Filament create page', function () {
    $pages = glob(base_path('modules/control-panel-*-filament/src/Resources/*/Pages/Create*.php')) ?: [];
    $tenantPages = array_values(array_filter($pages, static fn (string $page): bool => str_contains((string) file_get_contents($page), 'current_team_id')));

    expect($tenantPages)->not->toBeEmpty();

    foreach ($tenantPages as $page) {
        $contents = (string) file_get_contents($page);

        expect($contents)
            ->toContain('abort_if(')
            ->toContain('current_team_id')
            ->toContain("403, 'A current team is required.'")
            ->and($page)->toContain('modules/control-panel-');
    }
});
