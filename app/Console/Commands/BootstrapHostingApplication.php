<?php

namespace App\Console\Commands;

use DateInterval;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

#[Signature('hosting:bootstrap {--seed-policy=never : never, first-create or each-release} {--seeder=Database\\Seeders\\DatabaseSeeder : Approved seeder class}')]
#[Description('Run production migrations with explicit seeding policy under an isolated deployment lock')]
class BootstrapHostingApplication extends Command implements Isolatable
{
    public function handle(Migrator $migrator): int
    {
        $policy = $this->option('seed-policy');
        $seeder = $this->option('seeder');
        if (! in_array($policy, ['never', 'first-create', 'each-release'], true)
            || ! is_string($seeder) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $seeder)) {
            $this->error('Choose a supported seed policy and an approved seeder class.');

            return self::INVALID;
        }
        $firstCreation = ! $migrator->repositoryExists() || $migrator->getRepository()->getRan() === [];
        $seed = $policy === 'each-release' || ($policy === 'first-create' && $firstCreation);

        return $this->call('migrate', [
            '--force' => true, '--no-interaction' => true,
            ...($seed ? ['--seed' => true, '--seeder' => $seeder] : []),
        ]);
    }

    public function isolatableId(): string
    {
        $connection = DB::connection();

        return hash('sha256', implode(':', [$connection->getDriverName(), $connection->getConfig('host') ?? '', $connection->getDatabaseName()]));
    }

    public function isolationLockExpiresAt(): DateInterval
    {
        return new DateInterval('PT1H');
    }
}
