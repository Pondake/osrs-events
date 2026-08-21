<?php

namespace App\Http\Controllers;

use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
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
     * Tick a square on or off for the viewer's competitor.
     *
     * A toggle rather than separate mark/unmark endpoints: the thing being
     * expressed is "this is done", and a mis-click needs an undo that costs
     * the same as the click did.
     */
    public function toggle(Request $request, Event $event, BingoSquare $square, BoardAccessService $access, BingoService $bingo): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        // The square must belong to THIS event's card. Without it, a square
        // id from any other event would tick against this one — the same
        // class of bug as comparing a board id to an event id.
        abort_unless($square->bingo_card_id === $event->bingoCard?->id, 404);

        $competitor = $bingo->competitorFor($event, $request->user());

        if ($competitor === null) {
            return back()->with('board-save-error', trans('bingo.no_team'));
        }

        $existing = BingoCompletion::where('bingo_square_id', $square->id)
            ->where('team_id', $competitor['team_id'])
            ->where('user_id', $competitor['user_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return back()->with('board-save', trans('bingo.square_cleared'));
        }

        BingoCompletion::create([
            ...$competitor,
            'bingo_square_id' => $square->id,
            'marked_by' => $request->user()->id,
        ]);

        return back()->with('board-save', trans('bingo.square_marked'));
    }

    /** Set what a square asks for. Authors only, like the tile editor. */
    public function updateSquare(Request $request, Event $event, BingoSquare $square): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        abort_unless($request->user()->canEditEvent($event), 403);
        abort_unless($square->bingo_card_id === $event->bingoCard?->id, 404);

        $data = $request->validate([
            'task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
            'title_override' => ['nullable', 'string', 'max:255'],
        ]);

        $square->update([
            'task_id' => $data['task_id'] ?? null,
            'title_override' => $data['title_override'] ?: null,
        ]);

        return back()->with('board-save', trans('bingo.square_saved'));
    }

    /**
     * Change the card's size or win condition.
     *
     * Growing a card adds squares; shrinking one **refuses** rather than
     * dropping the squares that fall outside the new grid, because those
     * carry other people's completions. Deleting somebody's progress is not
     * something a size dropdown should be able to do silently.
     */
    public function updateCard(Request $request, Event $event, BingoService $bingo): RedirectResponse
    {
        abort_unless($event->type === 'BINGO', 404);
        abort_unless($request->user()->canEditEvent($event), 403);

        $card = $event->bingoCard;
        abort_unless($card !== null, 404);

        $data = $request->validate([
            'size' => ['sometimes', 'integer', Rule::in(\App\Models\BingoCard::SIZES)],
            'win_condition' => ['sometimes', Rule::in(\App\Models\BingoCard::WIN_CONDITIONS)],
        ]);

        if (isset($data['size']) && $data['size'] < $card->size) {
            $hasProgress = BingoCompletion::whereIn(
                'bingo_square_id',
                $card->squares()->where('position', '>=', $data['size'] ** 2)->select('id'),
            )->exists();

            if ($hasProgress) {
                return back()->with('board-save-error', trans('bingo.cannot_shrink'));
            }

            $card->squares()->where('position', '>=', $data['size'] ** 2)->delete();
        }

        $card->update($data);
        $bingo->ensureSquares($card->fresh());

        return back()->with('board-save', trans('bingo.card_saved'));
    }
}
