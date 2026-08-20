<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Grants the ADMIN role to the real Discord account (marthijnb / discord
 * username "mbeetje") that owns this project. Unlike AdminUserSeeder (a
 * synthetic local-only test account), this targets a real row created by
 * an actual Discord OAuth login — so it only does anything once that login
 * has happened at least once. Safe to run repeatedly.
 */
class GrantOwnerAdminSeeder extends Seeder
{
    private const DISCORD_USERNAME = 'mbeetje';

    public function run(): void
    {
        $user = User::where('discord_username', self::DISCORD_USERNAME)->first();

        if (! $user) {
            $this->command->warn('No user with discord_username "'.self::DISCORD_USERNAME.'" yet — log in via Discord first, then re-run this seeder.');

            return;
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'ADMIN'],
            ['description' => 'Full access — manage boards, tiles, tasks and users'],
        );

        $user->assignRole($adminRole);

        // Same person as AdminUserSeeder's synthetic account, so the same
        // OSRS name. Only filled if it's still empty — a rename made in the
        // app is theirs, and a seeder must not undo it on every run.
        if (blank($user->osrs_username)) {
            $user->update(['osrs_username' => AdminUserSeeder::OWNER_OSRS_USERNAME]);
            $this->command->info('Set OSRS username '.AdminUserSeeder::OWNER_OSRS_USERNAME.'.');
        }

        $this->command->info("Granted ADMIN to {$user->discord_username} ({$user->id}).");
    }
}
