<?php

namespace App\Events\Channels;

use App\Events\Channels\Concerns\SignalsEventEdits;
use App\Models\Event;
use App\Services\BoardReviewService;
use App\Services\EventFinishService;
use App\Support\EventCard;

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
        // move_seq alongside the position: two rolls can finish on the same
        // tile, and without it that looks like nothing happened.
        $rows = $event->playerBoards()
            ->orderBy('player_boards.id')
            ->get(['player_boards.id', 'player_boards.current_position', 'player_boards.move_seq']);

        // The board itself, not only who is standing where. A host editing a
        // tile mid-event — putting a task on it, moving a ladder — is as
        // visible to everyone watching as a player moving, and it reached
        // nobody: the second browser kept the old board until it was
        // reloaded, snakes and ladders included. The bingo card streamed its
        // squares from the start; this is the same thing for the same reason.
        // Queried through the relation rather than read off the cached one,
        // for the same reason the tiles are listed at all: this instance is
        // 45 seconds old by the end of a connection.
        $tiles = $event->board()->first()?->tiles()
            ->orderBy('position')
            ->get(['position', 'task_id', 'title_override', 'type', 'target_position'])
            ?? collect();

        // Claim state, same reason bingo's fingerprint carries
        // claimsVersion(): a host approving a claim while a player still has
        // the page open is exactly the kind of change this channel exists
        // to reach, and it was previously invisible to it entirely — a
        // reviewed claim only ever showed up on the next full reload.
        $claimsVersion = $event->board === null ? '' : app(BoardReviewService::class)->claimsVersion($event->board);

        // Who has finished, and whether the event was closed by it. Without
        // this a win reached only the browser that scored it: everybody
        // else's page kept a live dice on an event that was already over
        // until they happened to reload. Two ids and a timestamp, so the
        // per-viewer-every-few-seconds budget this method has stays intact.
        $finishVersion = app(EventFinishService::class)->version($event);

        return md5(
            $rows->map(fn ($r) => "{$r->id}:{$r->current_position}:{$r->move_seq}")->implode('|')
            .'#'
            .$tiles->map(fn ($t) => "{$t->position}:{$t->task_id}:{$t->title_override}:{$t->type}:{$t->target_position}")->implode('|')
            .'#'
            .$claimsVersion
            .'#'
            .$finishVersion
            .'#'
            .$this->eventVersion($event)
        );
    }

    public function payload(Event $event): array
    {
        $players = $event->playerBoards()
            ->with(['user:id,discord_username,nickname,avatar_url', 'team:id,name,icon_url,guild_id,guild_icon'])
            ->orderByDesc('player_boards.current_position')
            ->get([
                'player_boards.id',
                'player_boards.user_id',
                'player_boards.team_id',
                'player_boards.current_position',
                // The move that put them there, so every open board can play it
                // rather than snapping the piece across. A move is a fact
                // about the board, so it belongs on a shared channel.
                'player_boards.move_seq',
                'player_boards.last_move_from',
                'player_boards.last_move_landed',
                'player_boards.last_move_jump',
            ]);

        return [
            'event_version' => $this->eventVersion($event),
            // The event itself, so an edit arrives on the connection that is
            // already open. Sending a version and letting the page re-ask
            // cost a second request, which on a single-worker dev server
            // queues behind this very stream — the edit showed up thirty
            // seconds late, and the delay looked like the feature.
            'event' => EventCard::fresh($event),
            // A public hash, not the claims themselves — what a host
            // decided about YOUR claim is yours, and cannot ride a channel
            // every viewer shares. The page watches this and re-fetches its
            // own copy only when it actually changes, same pattern as
            // BingoChannel's claims_version.
            'claims_version' => $event->board === null ? null : app(BoardReviewService::class)->claimsVersion($event->board),
            // The podium, so a page that was open when somebody got home
            // updates in place. Named, like the player rows below it: a
            // shared channel cannot anonymise itself for some readers, so
            // EventStreamController gates the connection on
            // canSeeParticipants() instead — everybody holding one has
            // already passed that check. Anonymising here as well took the
            // podium away from the players it belongs to: on a listed
            // invite-only event their own names turned into "Anonymous
            // player" three seconds after the page rendered them.
            'finishes' => app(EventFinishService::class)->places($event),
            'players' => $players->map(fn ($pb) => [
                ...$pb->only(['id', 'user_id', 'team_id', 'current_position', 'move_seq', 'last_move_from', 'last_move_landed', 'last_move_jump']),
                'user' => $pb->user,
                'team' => $pb->team,
            ])->all(),
            // Shaped exactly as BoardController::show sends them, so the page
            // can swap one list for the other without knowing where it came
            // from — this select list wasn't actually keeping that promise:
            // BoardController::show() eager-loads the full Task via
            // 'board.tiles.task' with no column restriction, so the initial
            // page load had description and wiki_url; this channel dropped
            // both the moment the first live update landed and overwrote
            // liveTiles with it — which on a fast connection is almost
            // immediately. Reported directly: the current-task card showed a
            // title and nothing else, no description, no wiki link, despite
            // both being set on the task. Same class of bug already fixed
            // once on BingoChannel's squares.task select.
            'tiles' => $event->board()->first()?->tiles()
                ->with('task:id,title,description,icon_url,wiki_url')
                ->orderBy('position')
                ->get()
                ->all() ?? [],
        ];
    }
}
