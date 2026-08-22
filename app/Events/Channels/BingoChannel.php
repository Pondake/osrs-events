<?php

namespace App\Events\Channels;

use App\Models\BingoCompletion;
use App\Models\Event;
use App\Services\BingoService;

/**
 * Bingo.
 *
 * A card changes for reasons a race's standings never do — somebody claims a
 * square, a host approves or rejects one, an author reweights a tile — and
 * every one of those is visible to everybody watching. That is exactly what a
 * live channel is for, so bingo gets one for the same reason the races did.
 *
 * The pushed payload is the **public** view: the standings, and the squares
 * themselves. A viewer's own pending claims are private to them and stay on
 * the page render, because one stream shared by every viewer cannot carry
 * per-viewer state.
 */
class BingoChannel implements EventChannel
{
    public function __construct(private readonly BingoService $bingo) {}

    public function name(): string
    {
        return 'bingo';
    }

    public function fingerprint(Event $event): string
    {
        $card = $event->bingoCard;

        if ($card === null) {
            return 'no-card';
        }

        // Every claim's competitor, square and status. Deliberately not
        // updated_at: a host re-reviewing a claim to the same verdict rewrites
        // that column without changing anything anyone can see.
        $rows = BingoCompletion::query()
            ->join('bingo_squares', 'bingo_squares.id', '=', 'bingo_completions.bingo_square_id')
            ->where('bingo_squares.bingo_card_id', $card->id)
            ->orderBy('bingo_squares.position')
            ->orderBy('bingo_completions.id')
            ->get([
                'bingo_completions.team_id',
                'bingo_completions.user_id',
                'bingo_completions.status',
                'bingo_squares.position',
            ]);

        // The squares are part of the view too, so an author editing a tile
        // mid-event reaches everyone looking at the card.
        $squares = $card->squares()
            ->orderBy('position')
            ->get(['position', 'task_id', 'title_override', 'points', 'is_wildcard']);

        // The card's own rules are part of what everybody is looking at, and
        // they were missing. The payload carries winLines so that "a host
        // changing which shapes count mid-event reaches every open card" —
        // except the fingerprint never noticed, so it reached nobody. The win
        // condition is worse than cosmetic: it decides the standings, so an
        // open card went on scoring by a rule that had been switched off.
        $rules = implode(':', [
            $card->win_condition,
            $card->line_bonus,
            implode(',', $card->winLines()),
        ]);

        return md5(
            $rows->map(fn ($r) => "{$r->position}:{$r->team_id}{$r->user_id}:{$r->status}")->implode('|')
            .'#'
            .$squares->map(fn ($s) => "{$s->position}:{$s->task_id}:{$s->title_override}:{$s->points}:{$s->is_wildcard}")->implode('|')
            .'#'
            .$rules
        );
    }

    public function payload(Event $event): array
    {
        $card = $event->bingoCard;

        if ($card === null) {
            return ['standings' => [], 'squares' => [], 'approvedBy' => []];
        }

        $card->load('squares.task:id,title,icon_url');

        return [
            'standings' => $this->bingo->standings($event, $card)->all(),
            'winLines' => $card->winLines(),
            // Public by definition — an approved claim is already visible in
            // the standings, so putting the same fact on the square it was
            // approved for carries nothing new about anyone.
            'approvedBy' => $this->bingo->approvedBy($card),
            'squares' => $card->squares->sortBy('position')->values()->map(fn ($square) => [
                'id' => $square->id,
                'position' => $square->position,
                'label' => $square->label(),
                'iconUrl' => $square->task?->icon_url,
                'points' => $square->points,
                'titleOverride' => $square->title_override,
                'isWildcard' => $square->is_wildcard,
                'task' => $square->task,
            ])->all(),
        ];
    }
}
