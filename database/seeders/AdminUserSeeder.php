<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a local-only admin test account from ADMIN_USER (.env) — no real
 * Discord app credentials exist in this dev environment, so this plus the
 * ordinary login form — the /dev-login shortcut this used to feed was
 * removed 2026-08-21 (routes/web.php, environment('local')
 * only) is the only way to actually exercise admin-only pages without them.
 * Skips cleanly if ADMIN_USER isn't set rather than failing the whole seed
 * run — someone running `php artisan migrate:fresh --seed` without having
 * filled in .env yet still gets the rest of the seed data.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * The project owner's OSRS account. Hardcoded rather than read from .env
     * because it is a fact about who owns this project, not a per-deployment
     * setting — the same name the Ko-fi link points at.
     *
     * It matters that the seeded admin has one at all: every account needs an
     * OSRS username now (RequireOsrsUsername), so an admin seeded without one
     * would land on the gate page instead of the admin area.
     */
    public const OWNER_OSRS_USERNAME = 'Pondake';

    public function run(): void
    {
        $username = env('ADMIN_USER');
        $password = env('ADMIN_PASS');

        if (! $username || ! $password) {
            $this->command->warn('ADMIN_USER / ADMIN_PASS not set in .env — skipping admin test account. See .env.example.');

            return;
        }

        // An email and a password, because this account now logs in through
        // the ordinary form like everybody else. It used to have neither: the
        // only way in was /dev-login, a local-only route that took ADMIN_PASS
        // as a query parameter. Removing that route (2026-08-21) would have
        // left this account unreachable, which is the kind of thing you find
        // out at the worst moment.
        $email = env('ADMIN_EMAIL', 'admin@osrs-events.test');

        $adminRole = Role::firstOrCreate(
            ['name' => 'ADMIN'],
            ['description' => 'Full access — manage boards, tiles, tasks and users'],
        );

        // Fixed, distinct sentinel discord_id — never collides with a real
        // Discord snowflake (those are numeric strings ~18 digits) or the
        // DatabaseSeeder's prototype_player ('000000000000000001').
        $user = User::updateOrCreate(
            ['discord_id' => 'local-admin-seed'],
            [
                'discord_username' => $username,
                'avatar_url' => null,
                'osrs_username' => self::OWNER_OSRS_USERNAME,
                'email' => $email,
                // Re-hashed on every seed, so rotating ADMIN_PASS in .env and
                // re-running the seeder is the way to change it.
                'password' => Hash::make($password),
            ],
        );

        $user->assignRole($adminRole);

        $this->command->info("Seeded admin account: {$email} — log in at /login with ADMIN_PASS.");
    }
}
