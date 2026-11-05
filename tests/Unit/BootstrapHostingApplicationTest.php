<?php

use App\Console\Commands\BootstrapHostingApplication;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;

it('only seeds for the explicitly selected lifecycle policy and preserves migration failures', function (string $policy, bool $exists, array $ran, bool $seed): void {
    $repository = Mockery::mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('getRan')->andReturn($ran);
    $migrator = Mockery::mock(Migrator::class);
    $migrator->shouldReceive('repositoryExists')->once()->andReturn($exists);
    $migrator->shouldReceive('getRepository')->andReturn($repository);
    $command = Mockery::mock(BootstrapHostingApplication::class)->makePartial();
    $command->shouldReceive('option')->with('seed-policy')->andReturn($policy);
    $command->shouldReceive('option')->with('seeder')->andReturn('Database\\Seeders\\DatabaseSeeder');
    $command->shouldReceive('call')->once()->withArgs(function (string $name, array $options) use ($seed): bool {
        expect($name)->toBe('migrate')->and($options['--force'])->toBeTrue()->and($options['--no-interaction'])->toBeTrue();
        expect($options['--seed'] ?? false)->toBe($seed);

        return true;
    })->andReturn(17);
    expect($command->handle($migrator))->toBe(17);
})->with([
    'never on empty database' => ['never', false, [], false],
    'first creation' => ['first-create', false, [], true],
    'empty migration history' => ['first-create', true, [], true],
    'existing database must not be seeded' => ['first-create', true, ['2026_create_users'], false],
    'explicit release seeding' => ['each-release', true, ['2026_create_users'], true],
]);

it('rejects unsupported seed policies before migration', function (): void {
    $this->artisan('hosting:bootstrap', ['--seed-policy' => 'surprise'])->assertExitCode(2);
});
