<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * What the browser suite (tests/e2e) walks, and nothing else.
 *
 * Its own seeder rather than DatabaseSeeder: that one draws ninety random
 * users and reads ADMIN_USER / ADMIN_PASS from .env, so two runs never see the
 * same site and a failure cannot be reproduced. Everything here is fixed, and
 * the suite looks it up by name.
 *
 * One account per seat instead of one account re-roled (which is what the
 * dev:persona command does for a manual pass): a fresh database has nobody to
 * protect, and separate accounts let the suite sign every seat in once and
 * keep the sessions.
 *
 * Runs against a throwaway database only — see tests/e2e/serve.js.
 */
class E2eSeeder extends Seeder
{
    /** Committed on purpose: the database is created and thrown away by the suite. */
    public const PASSWORD = 'E2e-Password-1';

    public function run(): void
    {
        Role::findOrCreate('PLAYER', 'web');
        Role::findOrCreate('ADMIN', 'web');
        Permission::findOrCreate('canCreateBoards', 'web');
        Permission::findOrCreate('canCreateTiles', 'web');

        foreach ($this->seats() as $key => $seat) {
            $this->seat($key, $seat);
        }

        User::where('discord_username', 'e2e_creator')->first()->givePermissionTo('canCreateBoards');
        User::where('discord_username', 'e2e_admin')->first()->assignRole('ADMIN');

        $this->call(PageSeeder::class);

        // The edge-case events the walkthrough skill asks for, owned by the
        // owner seat. Reused rather than repeated: it is the same list, and
        // two copies would drift.
        Artisan::call('dev:fixtures', ['--host' => 'e2e_owner']);

        $this->ladder();
        $this->coHost('e2e_cohost', ['Teams of four', 'Invite only night', 'E2E Ladder']);
    }

    /**
     * Keyed by the seat's name in tests/e2e/support/seats.js.
     *
     * @return array<string, array<string, mixed>>
     */
    private function seats(): array
    {
        return [
            'member' => [],
            'creator' => [],
            'cohost' => [],
            'owner' => [],
            'admin' => [],
            // Signed up through Discord, has not been through setup: no OSRS
            // name, no email, intro pending. The seat the intro is written for.
            'newcomer' => ['osrs_username' => null, 'email' => null, 'password' => null, 'onboarding_completed_at' => null],
            // Same, but registered with an email and a password, so the
            // intro has no Discord to offer and a password to protect.
            'emailer' => ['osrs_username' => null, 'discord_username' => null, 'discord_id' => null, 'onboarding_completed_at' => null],
            // Finished setup, has no email: announcements skip this account.
            'unreachable' => ['email' => null, 'password' => null],
            // The accounts that specs change for good, so that no other spec
            // reads them afterwards. Discord logins have neither an email nor a
            // password to begin with.
            'settler' => ['email' => null, 'password' => null],
            'leaver' => ['email' => null, 'password' => null],
            // An ordinary member an admin promotes, and an email/password one
            // who changes its address.
            'promotee' => [],
            'changer' => [],
        ];
    }

    /** @param  array<string, mixed>  $overrides */
    private function seat(string $key, array $overrides): void
    {
        $attributes = array_merge([
            'discord_id' => 'e2e-'.str_pad($key, 14, '0', STR_PAD_LEFT),
            'discord_username' => 'e2e_'.$key,
            'nickname' => ucfirst($key),
            'osrs_username' => 'E2E '.ucfirst($key),
            'email' => $key.'@e2e.test',
            'password' => self::PASSWORD,
            // Confirmed against the hiscores, which is the ordinary state; an
            // unconfirmed name puts a banner on every page.
            'osrs_verified_at' => now(),
            'onboarding_completed_at' => now(),
        ], $overrides);

        // An emailer is known by its email alone, so the lookup key differs.
        $lookup = $attributes['discord_username'] !== null
            ? ['discord_username' => $attributes['discord_username']]
            : ['email' => $attributes['email']];

        User::forceCreate(array_merge($lookup, $attributes));
    }

    /**
     * A plain, running, open Snakes & Ladders event with a full board — the
     * one the play-through specs join and roll on. The fixtures are all edges;
     * this is the middle.
     */
    private function ladder(): void
    {
        $owner = User::where('discord_username', 'e2e_owner')->firstOrFail();

        $event = Event::create([
            'title' => 'E2E Ladder',
            'type' => 'SNAKES_LADDERS',
            'description' => 'The ordinary case.',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDays(30),
        ]);

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);

        foreach (range(0, 24) as $position) {
            Tile::create([
                'board_id' => $board->id,
                'position' => $position,
                'type' => match ($position) {
                    7 => 'SNAKE',
                    4 => 'LADDER',
                    default => 'NORMAL',
                },
                'target_position' => match ($position) {
                    7 => 2,
                    4 => 12,
                    default => null,
                },
                'title_override' => 'Tile '.($position + 1),
            ]);
        }
    }

    /** @param  list<string>  $titles */
    private function coHost(string $username, array $titles): void
    {
        $user = User::where('discord_username', $username)->firstOrFail();

        foreach (Event::whereIn('title', $titles)->get() as $event) {
            BoardAuthor::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id], ['is_owner' => false]);
        }
    }
}
