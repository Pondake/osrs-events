<?php

namespace App\Events\Channels;

use App\Events\Channels\Concerns\SignalsEventEdits;
use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\Event;
use App\Services\BingoService;
use App\Support\EventCard;

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
    use SignalsEventEdits;

    public function __construct(private readonly BingoService $bingo) {}

    public function name(): string
    {
        return 'bingo';
    }

    public function fingerprint(Event $event): string
    {
        // A fresh read, not the cached relation: this instance was loaded
        // when the stream opened and is asked the same question for the next
        // 45 seconds, so `$event->bingoCard` would answer with the card as it
        // was when that viewer connected — rules included.
        $card = $event->bingoCard()->first();

        if ($card === null) {
            // Still carries the event's own version: a bingo event without a
            // card yet is a real state (the card is created on first view),
            // and renaming or rescheduling one has to reach whoever is
            // already looking at it.
            return 'no-card#'.$this->eventVersion($event);
        }

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
            // Size and approval as well as the winning shapes. The size draws
            // the grid, so a card resized mid-event left every other viewer
            // with the wrong number of columns; approval decides whether
            // clicking a square opens a claim or ticks it off, which is a
            // viewer playing by a rule that has been switched off. Neither
            // was noticed here, and the squares only cover the resize when
            // the count happens to change.
            $card->size,
            $card->requires_approval ? '1' : '0',
            $card->win_condition,
            $card->line_bonus,
            implode(',', $card->winLines()),
        ]);

        return md5(
            $this->claimsVersion($card)
            .'#'
            .$squares->map(fn ($s) => "{$s->position}:{$s->task_id}:{$s->title_override}:{$s->points}:{$s->is_wildcard}")->implode('|')
            .'#'
            .$rules
            .'#'
            .$this->eventVersion($event)
        );
    }

    /**
     * Every claim's competitor, square and verdict, as one value.
     *
     * Sent as well as fingerprinted, because a claim being ruled on is the
     * one change a viewer cannot be told about over a shared channel. What
     * the host decided about *your* square is yours — the note, the verdict,
     * whether it completed a line — so the page has to go and fetch its own
     * copy, and this is what tells it that there is something to fetch.
     * Without it a player watched the standings award them points while
     * their own square still read "waiting for review": reported as a card
     * rendering half pending, half approved.
     *
     * Deliberately not updated_at: a host re-reviewing a claim to the same
     * verdict rewrites that column without changing anything anyone can see.
     */
    private function claimsVersion(BingoCard $card): string
    {
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

        return md5($rows->map(fn ($r) => "{$r->position}:{$r->team_id}{$r->user_id}:{$r->status}")->implode('|'));
    }

    public function payload(Event $event): array
    {
        $card = $event->bingoCard()->first();

        if ($card === null) {
            return [
                'standings' => [],
                'squares' => [],
                'approvedBy' => [],
                'event_version' => $this->eventVersion($event),
                'event' => EventCard::fresh($event),
            ];
        }

        $card->load('squares.task:id,title,icon_url');

        return [
            'event_version' => $this->eventVersion($event),
            'claims_version' => $this->claimsVersion($card),
            // The event itself, so an edit arrives on the connection that is
            // already open. Sending a version and letting the page re-ask
            // cost a second request, which on a single-worker dev server
            // queues behind this very stream — the edit showed up thirty
            // seconds late, and the delay looked like the feature.
            'event' => EventCard::fresh($event),
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
