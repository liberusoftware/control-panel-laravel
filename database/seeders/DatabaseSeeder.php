<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\HostingPlanSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $adminPassword = env('INITIAL_ADMIN_PASSWORD') ?: Str::password(32);
        $adminUser = User::firstOrCreate([
            'email' => env('INITIAL_ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'name' => 'Admin User',
            'password' => Hash::make($adminPassword),
            'email_verified_at' => now(),
        ]);

        $team = Team::firstOrCreate([
            'name' => 'Default',
            'user_id' => $adminUser->id,
        ], [
            'personal_team' => false,
        ]);

        $adminUser->forceFill(['current_team_id' => $team->id])->save();
        $adminUser->teams()->syncWithoutDetaching([$team->id]);

        $this->call([
            HostingPlanSeeder::class,
            PermissionsSeeder::class,
            RolesSeeder::class,
        ]);

        setPermissionsTeamId($team->id);
        $adminUser->syncRoles(['super_admin']);

        if ($adminUser->wasRecentlyCreated) {
            $this->command?->warn("Initial admin password: {$adminPassword}");
        }
    }
}
