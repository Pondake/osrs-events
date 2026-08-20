<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\PlayerBoard;
use App\Models\Role;
use App\Models\Tile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $playerRole = Role::firstOrCreate(
            ['name' => 'PLAYER'],
            ['description' => 'Standaard spelerrol'],
        );

        $user = User::firstOrCreate(
            ['discord_id' => '000000000000000001'],
            [
                'discord_username' => 'prototype_player',
                'avatar_url' => null,
                // Every account needs one — see RequireOsrsUsername. Without
                // it this user would be redirected to the gate on login, so a
                // seeded account with no RSN is a seeded account you can't use.
                'osrs_username' => 'Prototype',
            ],
        );

        UserRole::firstOrCreate(['user_id' => $user->id, 'role_id' => $playerRole->id]);

        // Since the Board→Event split this is two rows, not one: the event
        // holds what the competition IS, the board holds only the Snakes &
        // Ladders payload. This seeder still wrote the pre-split shape, so
        // `db:seed` (and therefore `composer setup`) failed outright on
        // "table boards has no column named title".
        $isNewEvent = Event::where('title', 'Winter Clan Grind')->doesntExist();

        $event = Event::firstOrCreate(
            ['title' => 'Winter Clan Grind'],
            [
                'id' => (string) str()->uuid(),
                'type' => 'SNAKES_LADDERS',
                'description' => 'A 7x7 clan event board for the winter Skilling competition.',
                'mode' => 'SOLO',
                'access_mode' => 'OPEN',
                'is_listed' => true,
            ],
        );

        $board = Board::firstOrCreate(
            ['event_id' => $event->id],
            ['id' => (string) str()->uuid(), 'size' => 'SIZE_7X7'],
        );

        if ($isNewEvent) {
            BoardAuthor::create([
                'id' => (string) str()->uuid(),
                'event_id' => $event->id,
                'user_id' => $user->id,
                'is_owner' => true,
            ]);
        }

        if ($board->tiles()->count() === 0) {
            $tiles = collect(range(0, 48))->map(fn ($position) => [
                'id' => (string) str()->uuid(),
                'board_id' => $board->id,
                'position' => $position,
                'type' => match (true) {
                    in_array($position, [5, 17, 33]) => 'SNAKE',
                    in_array($position, [9, 21, 40]) => 'LADDER',
                    default => 'NORMAL',
                },
                'target_position' => match ($position) {
                    5 => 1, 17 => 8, 33 => 20,
                    9 => 15, 21 => 30, 40 => 47,
                    default => null,
                },
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Tile::insert($tiles->all());
        }

        $playerBoard = PlayerBoard::firstOrCreate(
            ['user_id' => $user->id, 'board_id' => $board->id],
            ['id' => (string) str()->uuid(), 'current_position' => 12],
        );

        if ($playerBoard->completedTiles()->count() === 0) {
            $completedPositions = range(0, 12);
            $completedTileIds = Tile::where('board_id', $board->id)
                ->whereIn('position', $completedPositions)
                ->pluck('id');

            foreach ($completedTileIds as $tileId) {
                CompletedTile::create([
                    'id' => (string) str()->uuid(),
                    'player_board_id' => $playerBoard->id,
                    'tile_id' => $tileId,
                ]);
            }
        }

        // The EVENT id is what the URL addresses, not the board's. They
        // coincide for rows the split migration created and differ for
        // everything made since, which is exactly the trap the backlog warns
        // about — this printed an unreachable link for any fresh seed.
        $this->command->info("Seeded event {$event->id} — visit /events/{$event->id}");

        $this->call(AdminUserSeeder::class);
        $this->call(GrantOwnerAdminSeeder::class);
        $this->call(DemoDataSeeder::class);
        // After the admin seeders — it links its entries to whichever user
        // holds the ADMIN role.
        $this->call(AuditLogSeeder::class);
    }
}
