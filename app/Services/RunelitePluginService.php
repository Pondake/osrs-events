<?php

namespace App\Services;

use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PluginCompletion;
use App\Models\PluginToken;
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
        private TargetProgressService $progress,
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

    /** What the settings page shows, and what its live stream pushes. */
    public function status(User $user): array
    {
        $token = PluginToken::where('user_id', $user->id)->first();
        $watch = $this->watchSummary($user);

        return [
            'connection' => $this->connectionStatus($token),
            // Whether a client has ever reported this account playing the
            // name on it. Shown here because this page is where somebody
            // goes to fix it, and the fix is "connect once".
            'proven' => [
                'at' => $user->osrs_proven_at?->toIso8601String(),
                'via' => $user->osrs_proven_via,
            ],
            'watching' => $watch,
            'reports' => PluginCompletion::where('user_id', $user->id)
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn (PluginCompletion $completion) => [
                    'id' => $completion->id,
                    'name' => $completion->name,
                    'createdAt' => $completion->created_at->toIso8601String(),
                    'claims' => collect($completion->claims ?? [])->map(fn (array $claim) => [
                        'label' => $claim['label'] ?? $claim['name'] ?? null,
                        'eventTitle' => $claim['event_title'] ?? null,
                        'status' => $claim['status'] ?? null,
                    ])->all(),
                ])
                ->all(),
        ];
    }

    /** Cheap enough to poll every few seconds: no claim details, just what would change the display. */
    public function statusFingerprint(User $user): string
    {
        $token = PluginToken::where('user_id', $user->id)->first();
        $latest = PluginCompletion::where('user_id', $user->id)->latest('updated_at')->first();
        $watch = $this->watchSummary($user);

        return implode('|', [
            $token?->last_used_at?->timestamp ?? 'none',
            $user->osrs_proven_at?->timestamp ?? 'none',
            $latest?->id ?? 'none',
            $latest?->updated_at?->timestamp ?? 0,
            $watch['count'],
            $watch['events'],
        ]);
    }

    private function connectionStatus(?PluginToken $token): array
    {
        if ($token === null) {
            return ['state' => 'none', 'lastUsedAt' => null];
        }

        if ($token->last_used_at === null) {
            return ['state' => 'never', 'lastUsedAt' => null];
        }

        $state = $token->last_used_at->gt(now()->subMinutes(10)) ? 'connected' : 'stale';

        return ['state' => $state, 'lastUsedAt' => $token->last_used_at->toIso8601String()];
    }

    /** The distinct names the plugin watches for, and in how many events. */
    private function watchSummary(User $user): array
    {
        $matches = collect();
        $eventsWithTargets = 0;

        foreach ($this->openTargets($user) as ['targets' => $targets]) {
            if ($targets->isNotEmpty()) {
                $eventsWithTargets++;
            }

            $matches = $matches->merge($targets->pluck('match'));
        }

        return ['count' => $matches->unique()->count(), 'events' => $eventsWithTargets];
    }

    /**
     * Verdicts on this player's own plugin claims, newest first.
     *
     * The plugin announces the ones it has not seen before, so a host
     * approving a square shows up in game without the player refreshing a page.
     *
     * @return list<array>
     */
    public function recentVerdicts(User $user, int $limit = 10): array
    {
        $squares = BingoCompletion::query()
            ->where('marked_by', $user->id)
            ->where('completed_via', 'RUNELITE')
            ->whereNotNull('reviewed_at')
            ->with('square.card.event')
            ->latest('reviewed_at')
            ->limit($limit)
            ->get()
            ->map(fn (BingoCompletion $completion) => [
                'id' => $completion->id,
                'label' => $completion->square?->label(),
                'event_title' => $completion->square?->card?->event?->title,
                'status' => $completion->status,
                'reviewed_at' => $completion->reviewed_at->toIso8601String(),
            ]);

        $tiles = CompletedTile::query()
            ->where('marked_by', $user->id)
            ->where('completed_via', 'RUNELITE')
            ->whereNotNull('reviewed_at')
            ->with('tile.task', 'tile.board.event')
            ->latest('reviewed_at')
            ->limit($limit)
            ->get()
            ->map(fn (CompletedTile $completed) => [
                'id' => $completed->id,
                'label' => $completed->tile?->title_override ?: $completed->tile?->task?->title,
                'event_title' => $completed->tile?->board?->event?->title,
                'status' => $completed->status,
                'reviewed_at' => $completed->reviewed_at->toIso8601String(),
            ]);

        return $squares->merge($tiles)
            ->sortByDesc('reviewed_at')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * What this name did: the claims it made, and the counted targets it
     * moved forward without claiming.
     *
     * Both are answered, because a "do this N times" square that goes from
     * one to two is news even though it claimed nothing. The plugin says
     * "2 / 5" in game instead of sitting silent until the fifth kill.
     *
     * @return array{claims: list<array>, progress: list<array>}
     */
    public function complete(User $user, string $name, PluginCompletion $pluginCompletion): array
    {
        $match = RuneliteName::normalize($name);
        $claims = [];
        $progress = [];

        foreach ($this->openTargets($user) as ['event' => $event, 'targets' => $targets]) {
            foreach ($targets->where('match', $match) as $target) {
                // One report of at least N, not N reports adding up: a clan
                // that agreed only the 25-stack Soaked page counts did not
                // agree that twenty-five single pages count. A report under
                // the bar claims nothing and is not an error — the plugin
                // reports every drop, most of which are not the one.
                if ($pluginCompletion->quantity < $target['min_quantity']) {
                    continue;
                }

                // Something done before the event opened is not something
                // done in the event. The plugin reports what it sees, and a
                // client that was offline sends its backlog on reconnect.
                if ($event->start_date !== null && $pluginCompletion->occurred_at?->lt($event->start_date->copy()->startOfDay())) {
                    continue;
                }

                // A STOP-rule finish from the previous claim closes the event.
                if ($event->refresh()->isEnded()) {
                    break;
                }

                // "Do this N times": count this report and stop unless it was
                // the last one. The two modes stack — min_quantity above
                // decides whether a report qualifies at all, this decides how
                // many qualifying ones it takes, so "3 drops of at least 20"
                // is the two numbers together and neither can stand in for
                // the other.
                if ($target['required_count'] > 1) {
                    $done = $this->advance($event, $target, $user, $pluginCompletion);

                    // Null is a report that counted for nothing: a kill
                    // already counted, or no competitor to count it for.
                    if ($done === null) {
                        continue;
                    }

                    if ($done < $target['required_count']) {
                        $progress[] = [
                            'event_id' => $event->id,
                            'event_title' => $event->title,
                            ...self::describe($target),
                            'done' => $done,
                        ];

                        continue;
                    }
                }

                $status = $target['kind'] === 'bingo_square'
                    ? $this->claimSquare($event, $target['model'], $user, $pluginCompletion)
                    : $this->claimTile($event, $target['model'], $user, $pluginCompletion);

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

        return ['claims' => $claims, 'progress' => $progress];
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
            // Sent to the plugin as well as used here, so it can say what a
            // target is still waiting for instead of reporting a drop that
            // silently claims nothing.
            'min_quantity' => $target['min_quantity'],
            // How many qualifying reports it takes. 1 is "claim it on the
            // first one", which is every target set before this existed.
            'required_count' => $target['required_count'],
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
            // The bar this target sets. 1 means any amount counts, which is
            // every target configured before the column existed.
            'min_quantity' => $model->min_quantity,
            'required_count' => $model->required_count,
        ];
    }

    /**
     * Count this report toward a repetition target and answer how far the
     * competitor now is, or null when it counted for nothing.
     *
     * The competitor is resolved here rather than in the target, because it
     * is the same competitor the claim is written against — a team bingo
     * counts kills for the team, a board counts them for the player board.
     */
    private function advance(Event $event, array $target, User $user, PluginCompletion $pluginCompletion): ?int
    {
        if ($target['kind'] === 'bingo_square') {
            $competitor = $this->bingo->competitorFor($event, $user);

            if ($competitor === null) {
                return null;
            }

            $key = TargetProgressService::bingoKey($competitor);
        } else {
            $key = TargetProgressService::boardKey($this->playerBoards->getOrCreate($event, $user));
        }

        return $this->progress->record($target['kind'], $target['model']->id, $key, $pluginCompletion);
    }

    private function claimSquare(Event $event, BingoSquare $square, User $user, PluginCompletion $pluginCompletion): ?string
    {
        EventParticipant::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);

        $competitor = $this->bingo->competitorFor($event, $user);

        try {
            $completion = DB::transaction(fn () => BingoCompletion::create([
                ...$competitor,
                'bingo_square_id' => $square->id,
                'marked_by' => $user->id,
                'completed_via' => 'RUNELITE',
                'plugin_completion_id' => $pluginCompletion->id,
                'status' => $event->bingoCard->initialClaimStatus('RUNELITE', $user, filled($pluginCompletion->doubts)),
            ]));
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $this->notifier->teamScored($event, $completion->load('square', 'markedBy'));
        $this->finishes->evaluateBingo($event, $competitor);

        return $completion->status;
    }

    private function claimTile(Event $event, Tile $tile, User $user, PluginCompletion $pluginCompletion): ?string
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
                'plugin_completion_id' => $pluginCompletion->id,
                'marked_by' => $user->id,
                'status' => $event->board->initialClaimStatus('RUNELITE', $user, filled($pluginCompletion->doubts)),
            ]));
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $this->finishes->evaluateSnakesLadders($event, $playerBoard);

        return $completed->status;
    }
}
