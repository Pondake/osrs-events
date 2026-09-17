<?php

namespace App\Services;

use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Tile;
use App\Models\User;
use App\Support\RuneliteName;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the RuneLite plugin can complete, and completing it.
 *
 * Only a square or tile whose task links a wiki page is a target. Everything
 * else stays manual-only, and the manual routes keep working for targets too:
 * the plugin is never the only way to a tile.
 */
class RunelitePluginService
{
    public function __construct(
        private BoardAccessService $access,
        private BingoService $bingo,
        private PlayerBoardService $playerBoards,
        private BingoNotifier $notifier,
        private EventFinishService $finishes,
    ) {}

    /** @return Collection<int, array{event: Event, targets: Collection<int, array>}> */
    public function openTargets(User $user): Collection
    {
        return Event::query()
            ->playedBy($user)
            ->whereIn('type', ['BINGO', 'SNAKES_LADDERS'])
            ->whereNull('paused_at')
            ->with(['board', 'bingoCard'])
            ->orderBy('title')
            ->get()
            ->reject(fn (Event $event) => $event->isEnded() || $event->isUpcoming() || ! $this->access->hasAccess($user, $event))
            ->map(fn (Event $event) => [
                'event' => $event,
                'targets' => $event->type === 'BINGO' ? $this->squareTargets($event, $user) : $this->tileTargets($event, $user),
            ])
            ->values();
    }

    /** @return list<array> the claims this name created */
    public function complete(User $user, string $name): array
    {
        $match = RuneliteName::normalize($name);
        $claims = [];

        foreach ($this->openTargets($user) as ['event' => $event, 'targets' => $targets]) {
            foreach ($targets->where('match', $match) as $target) {
                // A STOP-rule finish from the previous claim closes the event.
                if ($event->refresh()->isEnded()) {
                    break;
                }

                $status = $target['kind'] === 'bingo_square'
                    ? $this->claimSquare($event, $target['model'], $user)
                    : $this->claimTile($event, $target['model'], $user);

                if ($status !== null) {
                    $claims[] = [
                        'event_id' => $event->id,
                        'event_title' => $event->title,
                        ...self::describe($target),
                        'status' => $status,
                    ];
                }
            }
        }

        return $claims;
    }

    public static function describe(array $target): array
    {
        return [
            'kind' => $target['kind'],
            'id' => $target['model']->id,
            'position' => $target['model']->position,
            'label' => $target['label'],
            'name' => $target['name'],
            'match' => $target['match'],
        ];
    }

    private function squareTargets(Event $event, User $user): Collection
    {
        $card = $event->bingoCard;
        $competitor = $card === null ? null : $this->bingo->competitorFor($event, $user);

        if ($competitor === null) {
            return collect();
        }

        $claimed = BingoCompletion::query()
            ->whereIn('bingo_square_id', $card->squares()->select('id'))
            ->where($competitor['team_id'] !== null ? 'team_id' : 'user_id', $competitor['team_id'] ?? $competitor['user_id'])
            ->pluck('bingo_square_id');

        return $card->squares()
            ->with('task')
            ->where('is_wildcard', false)
            ->whereNotIn('id', $claimed)
            ->orderBy('position')
            ->get()
            ->filter(fn (BingoSquare $square) => $square->task?->wiki_page_id !== null)
            ->map(fn (BingoSquare $square) => $this->target('bingo_square', $square, $square->label()))
            ->values();
    }

    private function tileTargets(Event $event, User $user): Collection
    {
        $board = $event->board;

        if ($board === null || ! $this->playerBoards->hasTeam($event, $user)) {
            return collect();
        }

        $playerBoard = $this->playerBoards->find($event, $user);

        $tile = Tile::query()
            ->with('task')
            ->where('board_id', $board->id)
            ->where('position', $playerBoard?->current_position ?? 0)
            ->where('type', 'NORMAL')
            ->first();

        if ($tile?->task?->wiki_page_id === null) {
            return collect();
        }

        if ($playerBoard !== null && CompletedTile::where('player_board_id', $playerBoard->id)->where('tile_id', $tile->id)->exists()) {
            return collect();
        }

        return collect([$this->target('tile', $tile, $tile->title_override ?: $tile->task->title)]);
    }

    private function target(string $kind, BingoSquare|Tile $model, ?string $label): array
    {
        return [
            'kind' => $kind,
            'model' => $model,
            'label' => $label,
            'name' => $model->task->title,
            'match' => RuneliteName::normalize($model->task->title),
        ];
    }

    private function claimSquare(Event $event, BingoSquare $square, User $user): ?string
    {
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);

        $competitor = $this->bingo->competitorFor($event, $user);

        try {
            $completion = DB::transaction(fn () => BingoCompletion::create([
                ...$competitor,
                'bingo_square_id' => $square->id,
                'marked_by' => $user->id,
                'completed_via' => 'RUNELITE',
                'status' => $event->bingoCard->initialClaimStatus('RUNELITE'),
            ]));
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $this->notifier->teamScored($event, $completion->load('square', 'markedBy'));
        $this->finishes->evaluateBingo($event, $competitor);

        return $completion->status;
    }

    private function claimTile(Event $event, Tile $tile, User $user): ?string
    {
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);

        $playerBoard = $this->playerBoards->getOrCreate($event, $user);

        try {
            $completed = DB::transaction(fn () => CompletedTile::create([
                'id' => (string) str()->uuid(),
                'player_board_id' => $playerBoard->id,
                'tile_id' => $tile->id,
                'completed_at' => now(),
                'completed_via' => 'RUNELITE',
                'marked_by' => $user->id,
                'status' => $event->board->initialClaimStatus('RUNELITE'),
            ]));
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $this->finishes->evaluateSnakesLadders($event, $playerBoard);

        return $completed->status;
    }
}
