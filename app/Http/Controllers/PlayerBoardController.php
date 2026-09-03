<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Tile;
use App\Services\BoardAccessService;
use App\Services\EventFinishService;
use App\Services\PlayerBoardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Ported from the old PlayersService — see PlayerBoardService for the SOLO/TEAM split. */
class PlayerBoardController extends Controller
{
    /**
     * Roll a d6, move the player, apply snake/ladder jumps, enforce the
     * board's daily roll limit. Landing on a snake un-completes every tile
     * between the snake's head and its target — same as the old
     * rollDice()'s "slide back down" behavior.
     */
    public function roll(Request $request, Event $event, BoardAccessService $access, PlayerBoardService $playerBoards, EventFinishService $finishes): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $event), 403);

        // Paused is not ended: the board stays readable, the standings stay
        // up, and nothing anyone did is lost — but nobody moves until the
        // host says so. Checked here rather than in the service because the
        // answer a player needs is a toast on the page they are already on.
        if ($event->isPaused()) {
            return back()->with('board-save-error', trans('events.paused_notice'));
        }

        if ($event->isEnded()) {
            return back()->with('board-save-error', trans('events.ended_notice'));
        }

        // The page already shows "Upcoming" for this — reported directly
        // from exactly that state, a board dated to start next month with a
        // working dice button, because nothing server-side checked the
        // start date at all.
        if ($event->isUpcoming()) {
            return back()->with('board-save-error', trans('events.not_started'));
        }

        // Tiles hang off the board, not the event — an event type without a
        // board has none.
        $tiles = $event->board?->tiles()->orderBy('position')->get() ?? collect();

        // The board's SIZE, not the number of tiles a host filled in: the page
        // renders the whole grid either way. Counting rows made the last
        // configured tile the finish line and clamped players back to it.
        // Floored at zero so an empty board cannot walk anyone to -1.
        $maxPosition = max(($event->board?->tileCount() ?? 1) - 1, 0);

        // Playing is joining. The button is the deliberate way in, but
        // somebody who rolls has said the same thing more plainly, and a
        // player missing from the participant list because they never pressed
        // it would be a worse kind of wrong than an unasked-for row.
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => Auth::id()]);

        $playerBoard = $playerBoards->getOrCreate($event, Auth::user());
        if ($playerBoard === null) {
            return back()->with('board-save-error', trans('events.no_team_yet'));
        }

        // Roll limit is board mechanics, so it moved with the board.
        $rollLimit = $event->board?->dice_roll_limit;

        if ($rollLimit !== null) {
            $isToday = $playerBoard->last_roll_date?->isToday() ?? false;
            $rollsToday = $isToday ? $playerBoard->dice_rolls_today : 0;

            if ($rollsToday >= $rollLimit) {
                return back()->with('board-save-error', trans('board.roll_limit_reached', ['limit' => $rollLimit]));
            }
        }

        $rolled = random_int(1, 6);

        // A forced number for working on the movement animation locally.
        // Gated on the environment, not a role: a chosen die on production
        // would be cheating with extra steps.
        if (app()->environment('local') && $request->filled('force')) {
            $forced = (int) $request->input('force');

            if ($forced >= 1 && $forced <= 6) {
                $rolled = $forced;
            }
        }

        $previousPosition = $playerBoard->current_position;
        $newPosition = min($previousPosition + $rolled, $maxPosition);
        $landedOn = $newPosition;

        $tile = $tiles->firstWhere('position', $newPosition);
        $jump = null;

        if ($tile && $tile->target_position !== null) {
            if ($tile->type === 'SNAKE') {
                $newPosition = $tile->target_position;
                $jump = 'snake';
            } elseif ($tile->type === 'LADDER') {
                $newPosition = $tile->target_position;
                $jump = 'ladder';
            }
        }

        DB::transaction(function () use ($playerBoard, $newPosition, $previousPosition, $jump, $landedOn, $tiles) {
            $isToday = $playerBoard->last_roll_date?->isToday() ?? false;

            $playerBoard->update([
                'current_position' => $newPosition,
                'dice_rolls_today' => $isToday ? $playerBoard->dice_rolls_today + 1 : 1,
                'last_roll_date' => now(),
                // The move itself, for the live stream. The sequence number is
                // what lets a viewer tell a new move from the same state
                // re-sent — two rolls can finish on the same tile.
                'move_seq' => $playerBoard->move_seq + 1,
                'last_move_from' => $previousPosition,
                'last_move_landed' => $landedOn,
                'last_move_jump' => $jump,
            ]);

            if ($jump === 'snake') {
                // Everything past where the snake dropped you, not just the
                // stretch it swallowed: a ladder can carry somebody above the
                // head, and that tile's completion belongs to a run that no
                // longer exists. The tile you land on goes too — you are
                // standing there again, so you do it again.
                $tileIdsToUncomplete = $tiles
                    ->filter(fn ($t) => $t->position >= $newPosition)
                    ->pluck('id');

                CompletedTile::where('player_board_id', $playerBoard->id)
                    ->whereIn('tile_id', $tileIdsToUncomplete)
                    ->delete();
            }
        });

        // A snake can swallow a completed tile, and the last one is not
        // exempt: ride one back from the finish and the board is no longer
        // finished. evaluateSnakesLadders() answers in both directions, so
        // the podium loses the row it should lose without anything here
        // needing to know that this particular roll was the undoing one.
        $finishes->evaluateSnakesLadders($event, $playerBoard->fresh());

        // Separate from the board-save flash text — DiceRoller.vue needs the
        // raw number to pick which face to render, not a pre-formatted
        // sentence to parse back apart.
        return back()
            ->with('board-save', $jump
                ? trans('board.rolled_with_jump', ['n' => $rolled, 'jump' => $jump])
                : trans('board.rolled', ['n' => $rolled]))
            ->with('last-roll', $rolled)
            ->with('last-move', [
                'from' => $previousPosition,
                'landed' => $landedOn,
                'to' => $newPosition,
                'jump' => $jump,
                // The stream carries the same number, so the page that rolled
                // recognises its own move coming back and does not walk it
                // twice.
                'seq' => $playerBoard->fresh()->move_seq,
            ]);
    }

    /**
     * Claim a tile, or withdraw a claim you already made.
     *
     * Same shape as BingoController::claim(): on a board that requires
     * approval this creates a PENDING claim for a host to review; on one
     * that doesn't it lands APPROVED immediately, exactly as a plain
     * self-toggle always did. The player's own claim is withdrawable while
     * it is still pending — once a host has ruled on it, changing it is the
     * host's call, same restriction bingo already enforces.
     */
    public function toggleTile(Request $request, Event $event, Tile $tile, BoardAccessService $access, PlayerBoardService $playerBoards, EventFinishService $finishes): RedirectResponse
    {
        abort_unless($access->hasAccess(Auth::user(), $event), 403);

        // The tile is bound by id alone, so nothing about the route says it
        // belongs to the board being played. Without this, a tile id from
        // any other board ticked off here — and counted towards progress
        // here, which on a competitive board is a way to win without
        // playing. Written with an explicit null check rather than
        // `$event->board?->id`, because null === null is true and a
        // board-less event would match every board-less tile.
        abort_unless($event->board !== null && $tile->board_id === $event->board->id, 404);

        // On hold, same as roll().
        if ($event->isPaused()) {
            return back()->with('board-save-error', trans('events.paused_notice'));
        }

        if ($event->isEnded()) {
            return back()->with('board-save-error', trans('events.ended_notice'));
        }

        // Same gap as roll() had, same fix.
        if ($event->isUpcoming()) {
            return back()->with('board-save-error', trans('events.not_started'));
        }

        // Same as roll(): ticking a tile off is playing, so it joins.
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => Auth::id()]);

        $playerBoard = $playerBoards->getOrCreate($event, Auth::user());
        if ($playerBoard === null) {
            return back()->with('board-save-error', trans('events.no_team_yet'));
        }

        $board = $event->board;

        $existing = CompletedTile::where('player_board_id', $playerBoard->id)
            ->where('tile_id', $tile->id)
            ->first();

        if ($existing) {
            // Undoing an APPROVAL is the host's call, not the claimant's —
            // same restriction bingo enforces. A REJECTION is deliberately
            // NOT locked the same way: bingo's squares are optional, so a
            // player stuck on one still finishes the card, but an S&L tile
            // is the one you are standing on — a rejected claim there with
            // no way back would brick the entire board for that player,
            // forever, since nothing after this tile is reachable without
            // completing it. Clearing it here lets them submit again with
            // better proof; the claim form back on the page is what asks
            // for it. A withdrawal (PENDING) is also a bare POST with no
            // body, which is why proof is validated below rather than here
            // — requiring it up front would have rejected every withdrawal
            // and every retry before this check ran.
            if ($existing->status === 'APPROVED' && $board->requires_approval) {
                return back()->with('board-save-error', trans('board.already_reviewed'));
            }

            $existing->delete();

            // Withdrawing the last tile's claim un-finishes the board — the
            // same call, in the same both-directions spirit, as the one on
            // the way in below.
            $finishes->evaluateSnakesLadders($event, $playerBoard);

            return back()->with('board-save', trans('board.tile_cleared'));
        }

        $data = $request->validate([
            // A URL, not an upload — same reasoning as bingo's claims:
            // clans already post screenshots to Discord or Imgur. Required
            // only when a host actually reviews claims — a board that
            // trusts self-toggles has nothing for a host to check it
            // against, so asking for it there would be asking for nothing.
            // Reported directly: submitting with every field blank silently
            // created a PENDING claim with nothing in it for a host to
            // judge — the whole point of the feature, unmet by its own form.
            'proof_url' => [$board->requires_approval ? 'required' : 'nullable', 'url', 'max:2048'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        CompletedTile::create([
            'id' => (string) str()->uuid(),
            'player_board_id' => $playerBoard->id,
            'tile_id' => $tile->id,
            // Stamped from the app's clock, not left to the column's
            // `useCurrent()` default. The default is the DATABASE's clock,
            // which is a different machine's idea of the time and a
            // different timezone's — and this column is no longer only a
            // display date: it is what decides who won, because a finish is
            // ordered by when it was submitted rather than when a host got
            // round to approving it. Two claims a minute apart came back
            // with the same timestamp under a travelled clock, which is what
            // turned this up.
            'completed_at' => now(),
            'completed_via' => 'MANUAL',
            'marked_by' => Auth::id(),
            'status' => $board->requires_approval ? 'PENDING' : 'APPROVED',
            'proof_url' => $data['proof_url'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        // Finishing is decided here, on the server, not by the browser
        // comparing a position against a tile count — that guess was lost on
        // every refresh and, on a board that reviews claims, fired the
        // celebration before the host had seen the proof. On such a board
        // this call finds a PENDING claim and stamps nothing; review() below
        // is what makes it true.
        $finishes->evaluateSnakesLadders($event, $playerBoard);

        return back()->with('board-save', $board->requires_approval
            ? trans('board.claim_submitted')
            : trans('board.tile_completed'));
    }

    /**
     * Approve or reject a pending claim. Hosts only — same split
     * BingoController::review() makes.
     *
     * A rejection keeps the row rather than deleting it, so the claimant can
     * see why.
     */
    public function review(Request $request, Event $event, CompletedTile $completedTile, EventFinishService $finishes): RedirectResponse
    {
        $this->assertCanEditEvent($request->user(), $event);

        // The claim must belong to THIS event's board — same guard
        // toggleTile() makes on the tile itself.
        abort_unless(
            $event->board !== null && $completedTile->tile?->board_id === $event->board->id,
            404,
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(['APPROVED', 'REJECTED'])],
            'review_note' => ['nullable', 'string', 'max:255'],
        ]);

        $completedTile->update([
            'status' => $data['status'],
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // The verdict is what decides a finish on a reviewed board, in both
        // directions: approving the last tile puts somebody on the podium,
        // and rejecting it later takes them back off again. Which is also
        // why the finish timestamp is the host's clock and not the
        // claimant's — on a board where a claim only counts once it has been
        // checked, being checked first is what being first means.
        $finishes->evaluateSnakesLadders($event, $completedTile->playerBoard);

        return back()->with('board-save', $data['status'] === 'APPROVED'
            ? trans('board.claim_approved')
            : trans('board.claim_rejected'));
    }
}
