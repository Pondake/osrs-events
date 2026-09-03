<?php

namespace App\Http\Controllers;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\BingoNotifier;
use App\Services\BingoService;
use App\Services\BoardAccessService;
use App\Services\EventFinishService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Playing and editing a bingo card.
 *
 * Toggling is a player action gated on access; editing a square is an author
 * action gated on canEditEvent — the same split TileController makes for
 * Snakes & Ladders, for the same reason.
 */
class BingoController extends Controller
{
    /**
     * Claim a square, or withdraw a claim you already made.
     *
     * On a card that requires approval this creates a PENDING claim for a
     * host to review; on one that doesn't it lands APPROVED immediately. The
     * player's own claim is withdrawable while it is still pending — once a
     * host has ruled on it, changing it is the host's call, not the
     * claimant's.
     */
    public function claim(Request $request, Event $event, BingoSquare $square, BoardAccessService $access, BingoService $bingo, BingoNotifier $notifier, EventFinishService $finishes): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        // The square must belong to THIS event's card. Without it, a square
        // id from any other event would score against this one — the same
        // class of bug as comparing a board id to an event id.
        $card = $event->bingoCard;
        abort_unless($card !== null && $square->bingo_card_id === $card->id, 404);

        // Late claims are refused rather than quietly accepted. Every guide on
        // running these says the cutoff is the thing hosts most need enforced,
        // and "submitted after it ended" is not a judgement call.
        if ($event->end_date !== null && $event->end_date->endOfDay()->isPast()) {
            return back()->with('board-save-error', trans('bingo.event_ended'));
        }

        // Same gap PlayerBoardController::roll() had — nothing here checked
        // the start date, so a card dated to start next month could be
        // claimed on today.
        if ($event->isUpcoming()) {
            return back()->with('board-save-error', trans('bingo.event_not_started'));
        }

        // A pause stops claims for the same reason but temporarily, and only
        // for players: review() below stays open, because clearing the queue
        // is often exactly why the host paused.
        if ($event->isPaused()) {
            return back()->with('board-save-error', trans('events.paused_notice'));
        }

        // Claiming a square is playing, so it joins. Bingo is the type where
        // this mattered most: taking part was inferred from having claimed
        // something, which meant an event nobody had scored in yet had, on
        // paper, nobody in it.
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $request->user()->id]);

        // Nothing to claim: a wildcard already counts for everybody, so a
        // claim on one would be a row that changes no score and a queue
        // entry a host has to rule on for no reason.
        if ($square->is_wildcard) {
            return back()->with('board-save-error', trans('bingo.wildcard_not_claimable'));
        }

        $competitor = $bingo->competitorFor($event, $request->user());

        if ($competitor === null) {
            return back()->with('board-save-error', trans('bingo.no_team'));
        }

        $existing = BingoCompletion::where('bingo_square_id', $square->id)
            ->where('team_id', $competitor['team_id'])
            ->where('user_id', $competitor['user_id'])
            ->first();

        if ($existing) {
            // Withdrawing your own pending claim is fine; undoing a host's
            // decision is not something the claimant gets to do.
            if ($existing->status !== 'PENDING' && $card->requires_approval) {
                return back()->with('board-save-error', trans('bingo.already_reviewed'));
            }

            $existing->delete();

            // Withdrawing a square can take a line apart again, which
            // un-wins the card. Answered in both directions by one call —
            // see EventFinishService.
            $finishes->evaluateBingo($event, $competitor);

            return back()->with('board-save', trans('bingo.square_cleared'));
        }

        // Validated here, not up front — a withdrawal above is a bare POST
        // with no body, and requiring proof up front would have rejected
        // every withdrawal before it ever reached that check. Required only
        // when a host actually reviews claims: a card with nothing to check
        // it against has no use for it. Submitting every field blank used to
        // silently create a PENDING claim with nothing for a host to judge —
        // the whole point of the review queue, unmet by its own form.
        $data = $request->validate([
            'proof_url' => [$card->requires_approval ? 'required' : 'nullable', 'url', 'max:2048'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $completion = BingoCompletion::create([
            ...$competitor,
            'bingo_square_id' => $square->id,
            'marked_by' => $request->user()->id,
            'status' => $card->requires_approval ? 'PENDING' : 'APPROVED',
            'proof_url' => $data['proof_url'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        // On a card with no approval step the claim IS the score, so the
        // team hears about it here — there is no review() call coming to do
        // it later. teamScored() is a no-op for a solo event and for a
        // pending claim, so this needs no condition of its own.
        $notifier->teamScored($event, $completion->load('square', 'markedBy'));

        // Winning the card is recorded rather than recomputed per viewer:
        // `hasWon` was true on every page load and stored nowhere, so a card
        // could be won and won again with no record of who did it first. On
        // a card that reviews claims this finds a PENDING claim and stamps
        // nothing — review() is what makes it true.
        $finishes->evaluateBingo($event, $competitor);

        return back()->with('board-save', $card->requires_approval
            ? trans('bingo.claim_submitted')
            : trans('bingo.square_marked'));
    }

    /**
     * Approve or reject a pending claim. Hosts only.
     *
     * A rejection keeps the row rather than deleting it, so the claimant can
     * see why and a host can see a pattern of re-submissions.
     */
    public function review(Request $request, Event $event, BingoCompletion $completion, BingoNotifier $notifier, EventFinishService $finishes): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        $this->assertCanEditEvent($request->user(), $event);

        $card = $event->bingoCard;
        abort_unless(
            $card !== null && $completion->square?->bingo_card_id === $card->id,
            404,
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(['APPROVED', 'REJECTED'])],
            'review_note' => ['nullable', 'string', 'max:255'],
        ]);

        $completion->update([
            'status' => $data['status'],
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // The claimant has been waiting on a human, which is the whole reason
        // this notification exists — the live stream only reaches somebody
        // who still has the card open, and nobody sits on a bingo card
        // waiting for a verdict.
        $completion->load('square', 'markedBy');
        $notifier->reviewed($event, $completion, $request->user());
        $notifier->teamScored($event, $completion);

        // The host's verdict decides the win, both ways round: approving the
        // square that completes a line puts that competitor on the podium,
        // rejecting it later takes them off again.
        $finishes->evaluateBingo($event, [
            'team_id' => $completion->team_id,
            'user_id' => $completion->user_id,
        ]);

        return back()->with('board-save', $data['status'] === 'APPROVED'
            ? trans('bingo.claim_approved')
            : trans('bingo.claim_rejected'));
    }

    /** Set what a square asks for. Authors only, like the tile editor. */
    public function updateSquare(Request $request, Event $event, BingoSquare $square): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        $this->assertCanEditEvent($request->user(), $event);
        abort_unless($square->bingo_card_id === $event->bingoCard?->id, 404);

        $data = $request->validate([
            'task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
            'title_override' => ['nullable', 'string', 'max:255'],
            // Tile weighting — harder squares score more, which is how these
            // events are actually run. Capped so one square cannot be worth
            // more than a whole card.
            'points' => ['nullable', 'integer', 'min:0', 'max:1000'],
            // A free square, counted as done for every competitor — see the
            // add_wildcard_to_bingo_squares migration.
            'is_wildcard' => ['nullable', 'boolean'],
        ]);

        // Every key null-coalesced, including title_override — `?:` on a
        // key the request did not send is an undefined-index 500, and a
        // PATCH that changes only one field is a perfectly ordinary request
        // (the wildcard toggle sends exactly that).
        $square->update([
            'task_id' => $data['task_id'] ?? null,
            'title_override' => ($data['title_override'] ?? null) ?: null,
            'points' => $data['points'] ?? 1,
            'is_wildcard' => $data['is_wildcard'] ?? false,
        ]);

        return back()->with('board-save', trans('bingo.square_saved'));
    }

    /**
     * Change the card's size, win condition, line bonus or review setting.
     *
     * The rules live in BingoService::applyCardSettings() — the event
     * settings modal writes the same fields and has to obey the same shrink
     * guard.
     */
    public function updateCard(Request $request, Event $event, BingoService $bingo): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        $this->assertCanEditEvent($request->user(), $event);

        $card = $event->bingoCard;
        abort_unless($card !== null, 404);

        $data = $request->validate([
            'size' => ['sometimes', 'integer', Rule::in(BingoCard::SIZES)],
            'win_condition' => ['sometimes', Rule::in(BingoCard::WIN_CONDITIONS)],
            'line_bonus' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'requires_approval' => ['sometimes', 'boolean'],
            'win_lines' => ['sometimes', 'array', 'min:1'],
            'win_lines.*' => [Rule::in(BingoCard::LINE_KINDS)],
        ]);

        if (! $bingo->applyCardSettings($card, $data)) {
            return back()->with('board-save-error', trans('bingo.cannot_shrink'));
        }

        return back()->with('board-save', trans('bingo.card_saved'));
    }
}
