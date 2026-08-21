<?php

namespace App\Events\Channels;

use App\Models\Event;

/**
 * Snakes & Ladders.
 *
 * Everyone's position on the board, which is what BoardShow's "show other
 * players" overlay draws. A roll moves one player and everybody watching
 * should see it, the same as a bingo square being ticked.
 */
class SnakesLaddersChannel implements EventChannel
{
    public function name(): string
    {
        return 'players';
    }

    public function fingerprint(Event $event): string
    {
        // Qualified column names throughout: playerBoards() is a
        // hasManyThrough, so the join brings boards' own columns into scope
        // and a bare name is ambiguous. That has caused a real 500 here.
        $rows = $event->playerBoards()
            ->orderBy('player_boards.id')
            ->get(['player_boards.id', 'player_boards.current_position']);

        return md5($rows->map(fn ($r) => "{$r->id}:{$r->current_position}")->implode('|'));
    }

    public function payload(Event $event): array
    {
        $players = $event->playerBoards()
            ->with(['user:id,discord_username,nickname,avatar_url', 'team:id,name,icon_url'])
            ->orderByDesc('player_boards.current_position')
            ->get([
                'player_boards.id',
                'player_boards.user_id',
                'player_boards.team_id',
                'player_boards.current_position',
            ]);

        return [
            'players' => $players->map(fn ($pb) => [
                ...$pb->only(['id', 'user_id', 'team_id', 'current_position']),
                'user' => $pb->user,
                'team' => $pb->team,
            ])->all(),
        ];
    }
}
