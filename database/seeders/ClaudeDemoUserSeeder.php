<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local-only demo account for automated browser checks and screenshots
 * (Claude Code). The password is committed on purpose: this account exists
 * only on a local install and holds no real data.
 *
 * AdminUserSeeder reads ADMIN_USER / ADMIN_PASS from .env, which are personal
 * credentials — hence a separate account rather than borrowing those.
 *
 * Onboarding is marked complete and an OSRS username is set, otherwise every
 * page bounces to the "enter your OSRS name" screen and a screenshot shows
 * that form instead of the app.
 *
 * Not called from DatabaseSeeder — run it explicitly:
 *
 *     php artisan db:seed --class=ClaudeDemoUserSeeder
 *
 * Idempotent: an existing account keeps its password and role.
 */
class ClaudeDemoUserSeeder extends Seeder
{
    public const EMAIL = 'claude-demo@absolit.nl';
    public const PASSWORD = 'ClaudeDemo!2026';
    public const OSRS_USERNAME = 'Claude Demo';

    public function run(): void
    {
        $user = User::firstOrNew(['email' => self::EMAIL]);

        if (! $user->exists) {
            $user->password = Hash::make(self::PASSWORD);
        }

        $user->nickname = 'Claude Demo';
        $user->osrs_username = self::OSRS_USERNAME;
        $user->onboarding_completed_at ??= now();
        $user->save();

        $this->command->info(sprintf(
            'Demo account ready: %s / %s (OSRS: %s)',
            self::EMAIL,
            self::PASSWORD,
            self::OSRS_USERNAME
        ));
    }
}
