<?php

namespace App\Events\Channels;

use App\Events\Channels\Concerns\SignalsEventEdits;
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
    use SignalsEventEdits;

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

        // The board itself, not only who is standing where. A host editing a
        // tile mid-event — putting a task on it, moving a ladder — is as
        // visible to everyone watching as a player moving, and it reached
        // nobody: the second browser kept the old board until it was
        // reloaded, snakes and ladders included. The bingo card streamed its
        // squares from the start; this is the same thing for the same reason.
        $tiles = $event->board?->tiles()
            ->orderBy('position')
            ->get(['position', 'task_id', 'title_override', 'type', 'target_position'])
            ?? collect();

        return md5(
            $rows->map(fn ($r) => "{$r->id}:{$r->current_position}")->implode('|')
            .'#'
            .$tiles->map(fn ($t) => "{$t->position}:{$t->task_id}:{$t->title_override}:{$t->type}:{$t->target_position}")->implode('|')
            .'#'
            .$this->eventVersion($event)
        );
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
            'event_version' => $this->eventVersion($event),
            'players' => $players->map(fn ($pb) => [
                ...$pb->only(['id', 'user_id', 'team_id', 'current_position']),
                'user' => $pb->user,
                'team' => $pb->team,
            ])->all(),
            // Shaped exactly as BoardController::show sends them, so the page
            // can swap one list for the other without knowing where it came
            // from.
            'tiles' => $event->board?->tiles()
                ->with('task:id,title,icon_url')
                ->orderBy('position')
                ->get()
                ->all() ?? [],
        ];
    }
}
