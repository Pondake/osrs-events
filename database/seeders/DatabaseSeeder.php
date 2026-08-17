<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\CompletedTile;
use App\Models\PlayerBoard;
use App\Models\Tile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'prototype@osrs-events.test'],
            ['name' => 'Prototype Player', 'password' => bcrypt(str()->random(32))],
        );

        $board = Board::create([
            'id' => (string) str()->uuid(),
            'title' => 'Winter Clan Grind',
            'description' => 'A 7x7 clan event board for the winter Skilling competition.',
            'size' => 'SIZE_7X7',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

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

        $playerBoard = PlayerBoard::create([
            'id' => (string) str()->uuid(),
            'user_id' => $user->id,
            'board_id' => $board->id,
            'current_position' => 12,
        ]);

        $completedPositions = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
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

        $this->command->info("Seeded board {$board->id} — visit /boards/{$board->id} after /login");
    }
}
