<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\CompletedTile;
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
            ['discord_username' => 'prototype_player', 'avatar_url' => null],
        );

        UserRole::firstOrCreate(['user_id' => $user->id, 'role_id' => $playerRole->id]);

        $isNewBoard = Board::where('title', 'Winter Clan Grind')->doesntExist();

        $board = Board::firstOrCreate(
            ['title' => 'Winter Clan Grind'],
            [
                'id' => (string) str()->uuid(),
                'description' => 'A 7x7 clan event board for the winter Skilling competition.',
                'size' => 'SIZE_7X7',
                'mode' => 'SOLO',
                'access_mode' => 'OPEN',
                'is_listed' => true,
            ],
        );

        if ($isNewBoard) {
            BoardAuthor::create([
                'id' => (string) str()->uuid(),
                'board_id' => $board->id,
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

        $this->command->info("Seeded board {$board->id} — visit /boards/{$board->id}");

        $this->call(AdminUserSeeder::class);
        $this->call(GrantOwnerAdminSeeder::class);
        $this->call(DemoDataSeeder::class);
        // After the admin seeders — it links its entries to whichever user
        // holds the ADMIN role.
        $this->call(AuditLogSeeder::class);
    }
}
