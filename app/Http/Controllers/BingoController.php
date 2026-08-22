<?php

namespace App\Http\Controllers;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\BingoService;
use App\Services\BoardAccessService;
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
    public function claim(Request $request, Event $event, BingoSquare $square, BoardAccessService $access, BingoService $bingo): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        // The square must belong to THIS event's card. Without it, a square
        // id from any other event would score against this one — the same
        // class of bug as comparing a board id to an event id.
        $card = $event->bingoCard;
        abort_unless($card !== null && $square->bingo_card_id === $card->id, 404);

        $data = $request->validate([
            // A URL, not an upload: clans already post screenshots to Discord
            // or Imgur, and becoming an image host to duplicate that is a
            // whole other set of problems.
            'proof_url' => ['nullable', 'url', 'max:2048'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Late claims are refused rather than quietly accepted. Every guide on
        // running these says the cutoff is the thing hosts most need enforced,
        // and "submitted after it ended" is not a judgement call.
        if ($event->end_date !== null && $event->end_date->endOfDay()->isPast()) {
            return back()->with('board-save-error', trans('bingo.event_ended'));
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

            return back()->with('board-save', trans('bingo.square_cleared'));
        }

        BingoCompletion::create([
            ...$competitor,
            'bingo_square_id' => $square->id,
            'marked_by' => $request->user()->id,
            'status' => $card->requires_approval ? 'PENDING' : 'APPROVED',
            'proof_url' => $data['proof_url'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

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
    public function review(Request $request, Event $event, BingoCompletion $completion): RedirectResponse
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
