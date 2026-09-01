<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BingoCard;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\Event;
use App\Models\EventBlueprint;
use App\Models\EventStanding;
use App\Models\Team;
use App\Models\User;
use App\Models\UserGuild;
use App\Notifications\EventStatusChanged;
use App\Services\BingoService;
use App\Services\BoardAccessService;
use App\Services\DiscordAnnouncer;
use App\Services\EventNotificationService;
use App\Services\EventParticipationService;
use App\Services\EventStandingsService;
use App\Services\BoardReviewService;
use App\Services\PlayerBoardService;
use App\Support\EventCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /** Which half of the create/edit form each field belongs to. */
    /** How many of each kind the hub shows before "view all". */
    private const HUB_SLICE = 3;

    private const EVENT_FIELDS = ['title', 'type', 'metric', 'description', 'mode', 'access_mode', 'required_guild_id', 'is_listed', 'start_date', 'end_date', 'discord_webhook_url'];

    private const BOARD_FIELDS = ['size', 'dice_roll_limit', 'requires_approval'];

    /**
     * The author's user is loaded by column, not whole.
     *
     * `authors` goes to the browser as-is from EventCard, and `User` marks
     * only password and remember_token hidden — so a bare `authors.user`
     * published every host's email address on every event page and every
     * board card. Naming the columns here fixes it for every caller at once;
     * the pages only ever read the name and the avatar.
     */
    private const EVENT_WITH = ['authors.user:id,discord_username,nickname,avatar_url', 'eventTeams.team', 'board', 'bingoCard'];

    /**
     * Public board list — only listed boards, matching the old
     * BoardsService::findAll(). Deliberately reachable without auth: it's
     * the page search engines index, so it can't sit behind a login.
     * Admins see unlisted ones too via Settings\Admin\BoardController.
     */
    public function index(): Response
    {
        $events = Event::where('is_listed', true)
            ->with(self::EVENT_WITH)
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Event $event) => EventCard::for($event));

        // Three separate rows rather than one "yours" bucket: what you run,
        // what you play, and what anyone can join are different questions,
        // and a host is very often not a participant in their own race.
        //
        // Scoped by involvement rather than by PlayerBoard: that row only
        // exists for Snakes & Ladders, so a race you entered or an event you
        // created yourself never appeared here at all, and the hub read as a
        // public directory with nothing of yours in it.
        $user = Auth::user();

        $slice = fn ($query) => $query
            ->with(self::EVENT_WITH)
            ->orderByDesc('start_date')
            ->take(self::HUB_SLICE)
            ->get()
            ->map(fn (Event $event) => EventCard::for($event))
            ->values();

        // Anything you host is already in the row above, so it is left out
        // here — the same card twice on one page reads as a bug.
        $playing = fn () => Event::playedBy($user)
            ->whereDoesntHave('authors', fn ($a) => $a->where('user_id', $user?->id));

        return Inertia::render('Boards/Index', [
            // The full list stays — the hub renders a slice of it and the
            // "view all" page renders the rest from the same prop.
            'boards' => $events,
            'hosted' => $slice(Event::hostedBy($user)),
            'hostedTotal' => Event::hostedBy($user)->count(),
            'playing' => $slice($playing()),
            'playingTotal' => $playing()->count(),
        ]);
    }

    /**
     * The full public list. Same page component as the hub, without the
     * slices — the hub links here when there is more than it shows.
     */
    public function all(): Response
    {
        $events = Event::where('is_listed', true)
            ->with(self::EVENT_WITH)
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Event $event) => EventCard::for($event));

        return Inertia::render('Boards/Index', [
            'boards' => $events,
            'mine' => [],
            'mineTotal' => 0,
            'showAll' => true,
        ]);
    }

    /**
     * The boards this user is actually playing — everything they hold a
     * PlayerBoard for, including unlisted and invite-only ones they've been
     * let into, which the public index above deliberately never shows.
     *
     * Progress is computed here rather than client-side because the tile
     * count comes from the board's size enum, and duplicating that mapping
     * into JS is how it drifts.
     */
    public function mine(Request $request, EventStandingsService $standings): Response
    {
        $tileCounts = Board::TILE_COUNTS;
        $user = Auth::user();

        // The hub shows three rows of three; this is where each one's "view
        // all" lands, so it has to be able to show that row on its own rather
        // than dumping everything and making the reader find it again.
        $filter = in_array($request->query('filter'), ['hosted', 'playing'], true)
            ? $request->query('filter')
            : 'all';

        // Driven by involvement, not by the rows one event type happens to
        // create. This was two queries — PlayerBoard for boards, EventStanding
        // for races — which meant bingo never appeared at all, and neither did
        // an event you host without playing in it. On a page called "my
        // events", anything missing is the bug.
        $query = match ($filter) {
            'hosted' => Event::hostedBy($user),
            'playing' => Event::playedBy($user)
                ->whereDoesntHave('authors', fn ($a) => $a->where('user_id', $user?->id)),
            default => Event::involving($user),
        };

        $events = $query
            ->with([...self::EVENT_WITH, 'board.tiles:id,board_id,position,type', 'bingoCard'])
            // One count per event via a subquery rather than N calls to
            // participants()->count() in the map() below — the difference
            // between one query and one-per-row on a page that can list
            // dozens of events.
            ->withCount('participants')
            ->orderByDesc('start_date')
            ->get();

        // Fetched once and matched in memory rather than per event, so the
        // page costs the same whether you are in three events or thirty.
        $playerBoards = $user->playerBoards()->with('completedTiles')->get()->keyBy('board_id');
        $standingRows = EventStanding::where('user_id', $user->id)->get()->keyBy('event_id');

        $bingoService = app(BingoService::class);

        $entries = $events->map(function (Event $event) use ($tileCounts, $playerBoards, $standingRows, $standings, $user, $bingoService) {
            $entry = [
                // What the row IS, for the icon and the meta line. The detail
                // block below is keyed on what data exists instead, so an
                // event you only host renders as itself rather than as a
                // half-filled participant row.
                'kind' => match ($event->type) {
                    'SNAKES_LADDERS' => 'board',
                    'BINGO' => 'bingo',
                    default => 'race',
                },
                'board' => EventCard::for($event),
                'isHost' => $event->authors->contains(fn ($a) => $a->user_id === $user->id),
                // From withCount('participants') above — a fact every kind
                // has, unlike progress/standing which only exist once you've
                // actually played, so it's the one number this row can
                // always show.
                'participants' => $event->participants_count,
                'progress' => null,
                'standing' => null,
            ];

            // A bingo event had nothing beside it on this page while a
            // Snakes & Ladders one got a board preview, so the row read as
            // less of an event than its neighbour. Its shape is the card:
            // the grid, and which squares are done.
            if ($event->type === 'BINGO' && $event->bingoCard) {
                $card = $event->bingoCard;

                $entry['card'] = [
                    'size' => $card->size,
                    'completed' => $bingoService->approvedPositions(
                        $card,
                        $bingoService->competitorFor($event, $user) ?? ['team_id' => null, 'user_id' => $user->id],
                    ),
                ];
            }

            if ($event->board && ($pb = $playerBoards->get($event->board->id))) {
                $total = $tileCounts[$event->board->size] ?? 49;
                $position = max(0, $pb->current_position);

                $entry['progress'] = [
                    'current' => $position + 1,
                    'total' => $total,
                    'pct' => $total <= 1 ? 0 : min(99, (int) floor(($position / ($total - 1)) * 100)),
                ];

                $entry['preview'] = [
                    'size' => $event->board->size,
                    'specialTiles' => $event->board->tiles
                        ->filter(fn ($tile) => $tile->type !== 'NORMAL')
                        ->map(fn ($tile) => ['position' => $tile->position, 'type' => $tile->type])
                        ->values(),
                    'currentPosition' => $position,
                    'completedPositions' => $event->board->tiles
                        ->whereIn('id', $pb->completedTiles->pluck('tile_id'))
                        ->pluck('position')
                        ->values(),
                ];
            }

            if ($standing = $standingRows->get($event->id)) {
                // Rank comes from the whole field, not the row — it is a
                // position among others. Bounded by how many races one person
                // has entered, which is a handful.
                $field = $standings->forEvent($event);

                $entry['standing'] = [
                    'rank' => $field->firstWhere('id', $standing->id)['rank'] ?? null,
                    'gained' => $standing->gained,
                    'syncedAt' => $standing->synced_at?->toIso8601String(),
                    'error' => $standing->sync_error,
                    'participants' => $field->count(),
                ];
            }

            return $entry;
        })->values();

        return Inertia::render('Boards/Mine', [
            'boards' => $entries,
            'filter' => $filter,
            'counts' => [
                'all' => Event::involving($user)->count(),
                'hosted' => Event::hostedBy($user)->count(),
                'playing' => Event::playedBy($user)
                    ->whereDoesntHave('authors', fn ($a) => $a->where('user_id', $user?->id))
                    ->count(),
            ],
        ]);
    }

    /**
     * Ported from AccessService::hasAccess() — the enforcement that was
     * missing entirely from the first migration pass (BoardAccessMode
     * existed as a column but nothing checked it; any logged-in user could
     * view any board). GUILD/INVITE boards the user hasn't joined render
     * Boards/AccessGate instead of the board itself.
     */
    public function show(Event $event, BoardAccessService $access, PlayerBoardService $playerBoards): Response|RedirectResponse
    {
        $user = Auth::user();

        // Reading, not taking part — see BoardAccessService::canView(). A
        // listed event opens for anyone, including signed-out visitors, which
        // is what `/events` has been promising strangers all along.
        if (! $access->canView($user, $event)) {
            // Not a refusal, a question: an unlisted event may well be theirs
            // to see once they are signed in, and the login page sends them
            // back here afterwards.
            if ($user === null) {
                return redirect()->guest(route('login'));
            }

            $canJoin = $access->canJoin($user, $event);

            return Inertia::render('Boards/AccessGate', [
                'board' => $event->only(['id', 'title', 'access_mode']),
                'reason' => $canJoin['reason'] ?? null,
                'canRequestInvite' => $event->access_mode === 'INVITE',
            ]);
        }

        $event->load([...self::EVENT_WITH, 'board.tiles.task']);

        // Each event type renders its own thing. A skill race has no grid, so
        // sending it to BoardShow would draw an empty board — the split exists
        // precisely so the page can differ with the type.
        // Every metric event renders the same standings page — a drop race is
        // a boss killcount race, so it differs from a skill race only in which
        // number is being counted.
        if ($event->needsMetric()) {
            return $this->showMetricRace($event, app(EventStandingsService::class));
        }

        if ($event->type === 'BINGO') {
            return $this->showBingo($event, app(BingoService::class));
        }

        // A read, not a get-or-create. This used to hand a player board to
        // anyone who opened the page, which made looking at a board the same
        // thing as playing it: every passer-by turned up in the player list
        // and on the leaderboard at square one. Joining is an explicit action
        // now and it is what creates the row — see EventParticipationService.
        // Null for a signed-out visitor, who has no progress to load and
        // is here to look. PlayerBoardService::find() types a real User,
        // so the guard is here rather than inside it — reading an event is
        // the only path that reaches these services without one.
        $playerBoard = $user === null ? null : $playerBoards->find($event, $user)?->load(
            'completedTiles:id,player_board_id,tile_id,status,proof_url,note,review_note,reviewed_at',
        );

        $canEdit = $user?->canEditEvent($event) ?? false;

        // Every player/team on the board with their current position — feeds
        // BoardShow.vue's "show other players" avatar stacks on tiles and the
        // sidebar's mini leaderboard preview. pathHasSnake/pathHasLadder mirror
        // LeaderboardController's same computation (kept duplicated rather than
        // extracted — it's a handful of lines with exactly two call sites).
        $tiles = $event->board?->tiles ?? collect();
        $maxPosition = $tiles->count() - 1;
        $namesArePublic = $access->canSeeParticipants($user, $event);
        $players = $event->playerBoards()
            ->with(['user:id,discord_username,nickname,avatar_url', 'team:id,name,icon_url'])
            ->orderByDesc('player_boards.current_position')
            // Qualified: playerBoards() is a hasManyThrough, so the join
            // brings boards' own id into scope and bare names are ambiguous.
            ->get(['player_boards.id', 'player_boards.user_id', 'player_boards.team_id', 'player_boards.current_position'])
            ->map(function ($pb) use ($tiles, $maxPosition, $namesArePublic) {
                $pathTiles = $tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);

                return [
                    ...$pb->only(['id', 'current_position']),
                    // Identity only where it is public — see
                    // BoardAccessService::canSeeParticipants(). On a listed
                    // invite-only event a stranger sees the pieces move and
                    // not whose they are, so user_id and team_id are dropped
                    // too: an id is an identity to anyone who can look it up.
                    'user_id' => $namesArePublic ? $pb->user_id : null,
                    'team_id' => $namesArePublic ? $pb->team_id : null,
                    'user' => $namesArePublic ? $pb->user : null,
                    'team' => $namesArePublic ? $pb->team : null,
                    'tilesRemaining' => $maxPosition - $pb->current_position,
                    'pathHasLadder' => $pathTiles->contains(fn ($t) => $t->type === 'LADDER' && $t->target_position !== null),
                    'pathHasSnake' => $pathTiles->contains(fn ($t) => $t->type === 'SNAKE' && $t->target_position !== null),
                ];
            });

        return Inertia::render('BoardShow', [
            // Flattened for the same reason the cards are — see EventCard.
            'board' => EventCard::for($event),
            'tiles' => $tiles,
            'playerBoard' => $playerBoard === null ? null : [
                ...$playerBoard->only(['id', 'current_position', 'dice_rolls_today']),
                // Only APPROVED counts toward progress — a pending claim
                // does not yet unlock the next roll. Same "only approved
                // scores" rule bingo's standings enforce.
                'completedTileIds' => $playerBoard->completedTiles->filter->isApproved()->pluck('tile_id')->values(),
                // Every claim this player made, whatever its state, keyed
                // by tile — they need to see their own pending or rejected
                // claim, or they will submit it again. Same shape bingo's
                // 'claims' prop uses.
                'claims' => $playerBoard->completedTiles->mapWithKeys(fn ($c) => [$c->tile_id => [
                    'id' => $c->id,
                    'status' => $c->status,
                    'proofUrl' => $c->proof_url,
                    'note' => $c->note,
                    'reviewNote' => $c->review_note,
                    'reviewedAt' => $c->reviewed_at?->toIso8601String(),
                ]]),
            ],
            'players' => $players,
            // Said explicitly rather than inferred from a null user: a row
            // with no user is otherwise indistinguishable from a deleted
            // account, and the page would label anonymous players as gone.
            'namesArePublic' => $namesArePublic,
            // A listed invite-only event opens for everyone now, so the
            // code field moved onto the page — see InviteCodeCard.
            'needsInvite' => $access->needsInvite($user, $event),
            // TEAM boards where the user isn't on any assigned team can't
            // play at all — BoardShow.vue renders a dedicated "no team on
            // this board" empty state instead of the grid for this case.
            'hasTeam' => $user !== null && $playerBoards->hasTeam($event, $user),
            'joined' => $user !== null && app(EventParticipationService::class)->has($user, $event),
            'canEdit' => $canEdit,
            // Only to somebody who may edit the event. A webhook URL is a
            // capability, not a setting: anyone holding it can post into that
            // Discord channel, so it must never ride along in EventCard,
            // which every viewer of a public event receives — and which the
            // live channel pushes to all of them every few seconds.
            'webhookUrl' => $canEdit ? $event->discord_webhook_url : null,
            'viewingAsAdmin' => $user !== null && app(BoardAccessService::class)->isAdminOnlyView($user, $event),
            'adminEditUrl' => $user === null ? null : $this->adminEditUrl($user, $event),
            // Hosts get the review queue on the same page as the board —
            // same reasoning as bingo's 'pending': leaving the thing you are
            // judging to judge it is the wrong shape.
            'pending' => $canEdit && $event->board
                ? app(BoardReviewService::class)->pendingQueue($event->board)
                : [],
        ]);
    }

    /**
     * Strip identities out of a payload while keeping the progress in it.
     *
     * The rule these two serve, decided 2026-08-31: a listed event's progress
     * is public — a board with no pieces on it is not the event anyone came
     * to look at — but its roster is not, unless the event is OPEN or the
     * reader is in it. See BoardAccessService::canSeeParticipants().
     *
     * Names are dropped rather than replaced with a placeholder here; the
     * pages render their own anonymous label, because "Player" is copy and
     * belongs in lang/en.json rather than in a controller.
     */
    private static function withoutHolderNames(array $bySquare): array
    {
        return array_map(fn (array $square) => [
            ...$square,
            'holders' => array_map(fn (array $holder) => [
                ...$holder,
                'name' => null,
                'avatarUrl' => null,
            ], $square['holders']),
        ], $bySquare);
    }

    /** Same rule, for a standings table: keep the score, drop the who. */
    private static function withoutCompetitorNames(Collection $rows): Collection
    {
        return $rows->map(fn (array $row) => [
            ...$row,
            // The id goes too — it identifies a user or team to anyone who
            // can look one up, which is the thing being withheld.
            'id' => null,
            'name' => null,
            'avatarUrl' => null,
        ])->values();
    }

    /**
     * The skill race screen: a standings table over XP gained in one skill.
     *
     * Modelled on Wise Old Man's competition view, which is the reference
     * implementation for this in the OSRS community — see the credit in the
     * README. Standings come from our own snapshot store rather than being
     * fetched per request, so the page is cheap and the live channel has one
     * source to push from.
     */
    /**
     * The bingo card, plus this viewer's own progress on it.
     *
     * `competitor` is null for a TEAM event where the viewer is on no
     * assigned team — they can look but not tick, and the page says so
     * rather than offering squares that would silently score against nobody.
     */
    private function showBingo(Event $event, BingoService $bingo): Response
    {
        $card = $event->bingoCard;

        // A BINGO event with no card is only reachable through direct data
        // manipulation, but rendering a hole is worse than making one.
        if ($card === null) {
            // Every column named, not left to the table's defaults: a
            // freshly created model carries what was passed to it and
            // nothing else, so `$card->size` came back null and the page
            // died on it a few lines further down. A database default only
            // reaches PHP on a re-read.
            $card = $event->bingoCard()->create([
                'id' => (string) str()->uuid(),
                'size' => 5,
                'win_condition' => 'LINE',
                'win_lines' => BingoCard::LINE_KINDS,
                'line_bonus' => 0,
                'requires_approval' => true,
            ]);
            $bingo->ensureSquares($card);
        }

        $card->load('squares.task:id,title,icon_url,description,wiki_url');

        $user = Auth::user();
        $canEdit = $user?->canEditEvent($event) ?? false;
        // Same guard as BoardShow's player board: a signed-out reader is
        // not a competitor, and competitorFor() types a real User.
        $competitor = $user === null ? null : $bingo->competitorFor($event, $user);

        // Progress is public on any listed event; who made it is not,
        // unless the event is OPEN or this reader is in it. See
        // BoardAccessService::canSeeParticipants().
        $bingoNamesArePublic = app(BoardAccessService::class)->canSeeParticipants($user, $event);

        // Every claim this viewer has made, whatever its state — they need to
        // see their own pending square sitting in the queue, or they will
        // claim it again.
        $claims = $competitor === null ? collect() : $bingo->claimsFor($card, $competitor);
        $approved = $claims->filter->isApproved()->keys()->map(fn ($p) => (int) $p)->all();

        return Inertia::render('Events/Bingo', [
            'event' => EventCard::for($event),
            'card' => [
                'size' => $card->size,
                'winCondition' => $card->win_condition,
                'lineBonus' => $card->line_bonus,
                'requiresApproval' => $card->requires_approval,
                // Which shapes count, so the page can draw the same lines the
                // server scores — a hint pointing at a diagonal on a
                // rows-only card would be a lie the grid tells.
                'winLines' => $card->winLines(),
                'squares' => $card->squares->sortBy('position')->values()->map(fn ($square) => [
                    'id' => $square->id,
                    'position' => $square->position,
                    'label' => $square->label(),
                    'iconUrl' => $square->task?->icon_url,
                    'points' => $square->points,
                    // For the editor: what is currently set, so opening a
                    // square shows its state rather than a blank form.
                    'titleOverride' => $square->title_override,
                    'isWildcard' => $square->is_wildcard,
                    'task' => $square->task,
                ]),
            ],
            // Keyed by position so the grid can colour a square by its state
            // without searching a list per cell.
            // The whole claim, not just its verdict. The square's dialog shows
            // what was submitted and what the host said back — a rejection
            // that explains nothing is the thing players complain about, and
            // a note left on an APPROVAL was being written and then thrown
            // away entirely.
            'claims' => $claims->map(fn ($claim) => [
                'id' => $claim->id,
                'status' => $claim->status,
                'reviewNote' => $claim->review_note,
                'proofUrl' => $claim->proof_url,
                'note' => $claim->note,
                'reviewedAt' => $claim->reviewed_at?->toIso8601String(),
            ]),
            'completed' => $approved,
            // Who holds each square, for the faces on the grid. Same source
            // the live channel pushes, so a card that updates mid-event does
            // not disagree with the one that was rendered.
            'needsInvite' => app(BoardAccessService::class)->needsInvite($user, $event),
            'approvedBy' => $bingoNamesArePublic
                ? $bingo->approvedBy($card)
                : self::withoutHolderNames($bingo->approvedBy($card)),
            'completedLines' => $bingo->completedLines($card->size, $approved, $card->winLines()),
            'hasWon' => $bingo->hasWon($card, $approved),
            'canPlay' => $competitor !== null,
            'joined' => app(EventParticipationService::class)->has($user, $event),
            'standings' => $bingoNamesArePublic
                ? $bingo->standings($event, $card)
                : self::withoutCompetitorNames($bingo->standings($event, $card)),
            // Hosts get the review queue on the same page as the card — a
            // separate screen for it would mean leaving the thing you are
            // judging to judge it.
            'pending' => $canEdit ? $bingo->pendingQueue($card) : [],
            'canEdit' => $canEdit,
            'webhookUrl' => $canEdit ? $event->discord_webhook_url : null,
            'viewingAsAdmin' => $user !== null && app(BoardAccessService::class)->isAdminOnlyView($user, $event),
            'adminEditUrl' => $user === null ? null : $this->adminEditUrl($user, $event),
        ]);
    }

    private function showMetricRace(Event $event, EventStandingsService $standings): Response
    {
        $user = Auth::user();

        return Inertia::render('Events/SkillRace', [
            'event' => EventCard::for($event),
            // The initial paint. From here the SSE stream owns the table, so
            // this is a snapshot rather than the only delivery — and it means
            // the page is complete before any JavaScript runs, which matters
            // for SSR and for anyone the stream never reaches.
            'needsInvite' => app(BoardAccessService::class)->needsInvite($user, $event),
            'standings' => app(BoardAccessService::class)->canSeeParticipants($user, $event)
                ? $standings->forEvent($event)
                : self::withoutCompetitorNames($standings->forEvent($event)),
            // Nothing to rank without an RSN — the hiscores are keyed by it.
            // The page prompts for one instead of silently leaving someone off
            // a leaderboard they think they entered.
            'osrsUsername' => $user?->osrs_username,
            'isParticipant' => app(EventParticipationService::class)->has($user, $event)
                || $event->standings()->where('user_id', $user?->id)->exists(),
            'canEdit' => $user?->canEditEvent($event) ?? false,
            'webhookUrl' => $user?->canEditEvent($event) ? $event->discord_webhook_url : null,
            'viewingAsAdmin' => $user !== null && app(BoardAccessService::class)->isAdminOnlyView($user, $event),
            'adminEditUrl' => $user === null ? null : $this->adminEditUrl($user, $event),
        ]);
    }

    /**
     * Direct invite-link join (GET /boards/{board}/join/{token}) — ported
     * from the old join/[token].vue. Unauthenticated visitors log in first —
     * on the login page, which offers Discord and email/password both; this
     * used to go straight to Discord, which an email account could not get
     * past. redirect()->intended() (Laravel's own mechanism, not a
     * hand-rolled localStorage flag like the old client-side version needed)
     * brings them right back here afterward.
     */
    public function joinByLink(Request $request, Event $event, string $token, EventParticipationService $participation): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        try {
            // The full join, not just the access row. Somebody who followed an
            // invite link has said what they want plainly enough — asking them
            // to press Join on arrival would be asking twice.
            $needsTeam = $participation->join($request->user(), $event, $token);
        } catch (ValidationException $e) {
            return redirect()->route('events.show', $event)->with('board-save-error', collect($e->errors())->flatten()->first() ?? trans('events.join_failed'));
        }

        return redirect()->route('events.show', $event)
            ->with('board-save', trans($needsTeam ? 'events.joined_needs_team' : 'board.joined'));
    }

    /**
     * Ported from BoardsService::create() — the creator is always the owner
     * (isOwner: true); any other author IDs submitted become co-editors.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateBoards'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Required on create, unlike update. An event without a window
            // is not an event — the whole model (standings read over a
            // period, late bingo claims refused, upcoming/live/ended badges)
            // assumes one, and every path that reads a null date has to
            // invent a fallback. The form pre-fills today and a fortnight
            // out, so requiring them costs nobody a keystroke.
            //
            // update() keeps them nullable on purpose: events created before
            // this rule exist with null dates, and a required field on edit
            // would make those uneditable until someone guessed a window for
            // them. It enforces the pairing instead — see there.
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            // Only creatable types — a planned one is advertised in the UI
            // as coming soon, which is not the same as being selectable.
            'type' => ['nullable', Rule::in(Event::availableTypes())],
            // Required only for the types that race on one, and rejected
            // for the ones that don't — a Snakes & Ladders event carrying a
            // metric would be a value nothing ever reads.
            // Required for the types that race on one, and checked against
            // that type's own list: a boss name is not a valid skill race and
            // vice versa.
            'metric' => ['nullable', 'required_if:type,SKILL_RACE,DROP_RACE', Rule::in(Event::allMetrics())],
            'size' => ['required_if:type,SNAKES_LADDERS', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            'bingo_size' => ['nullable', 'integer', Rule::in(BingoCard::SIZES)],
            'win_condition' => ['nullable', Rule::in(BingoCard::WIN_CONDITIONS)],
            'line_bonus' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'requires_approval' => ['nullable', 'boolean'],
            // At least one shape, or "first line wins" is a condition no card
            // can ever meet.
            'win_lines' => ['nullable', 'array', 'min:1'],
            'win_lines.*' => [Rule::in(BingoCard::LINE_KINDS)],
            'mode' => ['nullable', 'in:SOLO,TEAM'],
            'dice_roll_limit' => ['nullable', 'integer', 'min:1'],
            'is_listed' => ['nullable', 'boolean'],
            'access_mode' => ['nullable', 'in:OPEN,GUILD,INVITE'],
            // Conditionally required, because GUILD access with no guild is
            // not a restriction — it is an event nobody can join, saved
            // without complaint. The form labelled this "required" and the
            // server accepted it empty, which is the worst of both.
            'required_guild_id' => ['nullable', 'string', 'required_if:access_mode,GUILD'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['uuid', 'exists:users,id'],
            // Staged by BoardSettingsModal's Teams tab while the event is
            // still being created — there is no event id to POST them
            // against yet, so they ride along with the form that creates it.
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['uuid', 'exists:teams,id'],
            // Which template this started from, if any. Only its BOARD is
            // read here — the settings the form shows were applied in the
            // browser and arrive as ordinary fields, already validated above.
            // The layout cannot work that way: it is up to 81 rows written
            // after the event exists.
            'blueprint_id' => ['nullable', 'uuid', 'exists:event_blueprints,id'],
        ]);

        // One submission, two rows: the competition and its Snakes &
        // Ladders payload. EVENT_FIELDS / BOARD_FIELDS decide which half each
        // key belongs to, so the form stays one form.
        $event = DB::transaction(function () use ($data, $request) {
            $event = Event::create([
                'id' => (string) str()->uuid(),
                ...collect($data)->only(self::EVENT_FIELDS)->toArray(),
                'type' => $data['type'] ?? 'SNAKES_LADDERS',
                'mode' => $data['mode'] ?? 'SOLO',
                'is_listed' => $data['is_listed'] ?? true,
                'access_mode' => $data['access_mode'] ?? 'OPEN',
            ]);

            // Each type creates its own payload, or none. This is the seam
            // the event/board split exists for: a race has no grid, and a
            // bingo card is a different grid from a Snakes & Ladders board.
            if ($event->type === 'SNAKES_LADDERS') {
                $event->board()->create([
                    'id' => (string) str()->uuid(),
                    ...collect($data)->only(self::BOARD_FIELDS)->toArray(),
                ]);
            }

            if ($event->type === 'BINGO') {
                $card = $event->bingoCard()->create([
                    'id' => (string) str()->uuid(),
                    'size' => $data['bingo_size'] ?? 5,
                    'win_condition' => $data['win_condition'] ?? 'LINE',
                    'win_lines' => $data['win_lines'] ?? BingoCard::LINE_KINDS,
                    'line_bonus' => $data['line_bonus'] ?? 0,
                    // Defaults to on, matching the column default: a card
                    // nobody checks is a shared checklist, not a competition.
                    'requires_approval' => $data['requires_approval'] ?? true,
                ]);

                // Filled out immediately, unlike S&L tiles which appear on
                // first edit: a bingo card has to be clickable the moment it
                // exists, and a missing row renders as a hole in the grid.
                app(BingoService::class)->ensureSquares($card);
            }

            $extraAuthorIds = collect($data['author_ids'] ?? [])
                ->reject(fn ($id) => $id === $request->user()->id)
                ->unique();

            BoardAuthor::insert([
                ['id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $request->user()->id, 'is_owner' => true],
                ...$extraAuthorIds->map(fn ($id) => [
                    'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $id, 'is_owner' => false,
                ])->all(),
            ]);

            // Same rows addTeam() would write one at a time afterwards, and
            // only for a TEAM event — a solo event carrying team assignments
            // would be rows nothing ever reads. unique() because the form
            // could submit the same id twice and the pivot has no constraint
            // stopping it.
            $teamIds = collect($data['team_ids'] ?? [])->unique();
            if ($event->mode === 'TEAM' && $teamIds->isNotEmpty()) {
                BoardTeam::insert($teamIds->map(fn ($id) => [
                    'id' => (string) str()->uuid(), 'event_id' => $event->id, 'team_id' => $id,
                ])->all());
            }

            // The board the template brought with it, if it brought one.
            // After the payload rows exist, because a tile needs a board and
            // a square needs a card — and scoped to what this person may
            // actually start from, or a guessed id would be a way to read
            // another clan's layout.
            $blueprint = $data['blueprint_id'] ?? null
                ? EventBlueprint::whereKey($data['blueprint_id'])->visibleTo($request->user())->first()
                : null;

            $blueprint?->applyLayoutTo($event->fresh());

            return $event;
        });

        // ?setup=tiles opens the tile list editor on arrival. A board is
        // created empty, and "now go and turn on edit mode and click 49
        // squares" is not a next step anybody guesses — so the step that
        // actually finishes the job is offered rather than waited for. It is
        // a query parameter rather than a flash so a reload keeps it, and
        // dismissing the dialog clears it.
        return redirect()
            ->route('events.show', ['event' => $event, 'setup' => 'tiles'])
            ->with('board-save', trans('admin.board_created'));
    }

    /**
     * Ported from BoardsService::update() — author sync preserves existing
     * owners (they can't be replaced via this bulk update), same as the
     * original transaction: delete all non-owner authors, recreate the
     * submitted set minus anyone who's already an owner.
     */
    public function update(Request $request, Event $event, bool $asAdmin = false): RedirectResponse
    {
        $this->assertCanEditEvent($request->user(), $event, $asAdmin);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Nullable, unlike create — see the note there. What IS enforced
            // is the pairing: a start with no end is the one combination
            // that reads as a mistake rather than as "no dates set", since
            // every date-aware rule in the app keys off the end.
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'required_with:start_date', 'after_or_equal:start_date'],
            // Immutable after creation. The type decides which payload table
            // holds the event, and neither direction survives a swap: turning
            // a board event into a race orphans its board, its tiles and
            // everyone's progress, and turning a race into a board event
            // leaves it with no board at all — an empty grid nobody can play.
            // Rebuilding the payload silently is worse than refusing, because
            // one of those directions destroys data.
            'type' => [
                'sometimes',
                Rule::in(Event::availableTypes()),
                fn ($attribute, $value, $fail) => $value === $event->type
                    ? null
                    : $fail(trans('validation.event_type_immutable')),
            ],
            'metric' => ['nullable', Rule::in(Event::allMetrics())],
            'size' => ['sometimes', 'nullable', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            // A bingo event's card settings ride the same form as the rest
            // of its settings, so the edit modal holds the whole event
            // rather than sending people to a second place for the half of
            // it that decides how the card is won.
            //
            // `nullable` as well as `sometimes`, which is the difference
            // between working and not: one form carries every type's fields,
            // so editing a Snakes & Ladders event submits bingo_size as
            // null. `sometimes` only skips a key that is ABSENT — a present
            // null is validated, so every non-bingo save failed with "the
            // card size field must be an integer" about a card the event
            // does not have. The client drops them too; this is the half
            // that has to hold.
            'bingo_size' => ['sometimes', 'nullable', 'integer', Rule::in(BingoCard::SIZES)],
            'win_condition' => ['sometimes', 'nullable', Rule::in(BingoCard::WIN_CONDITIONS)],
            'line_bonus' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000'],
            'requires_approval' => ['sometimes', 'nullable', 'boolean'],
            'win_lines' => ['sometimes', 'nullable', 'array', 'min:1'],
            'win_lines.*' => [Rule::in(BingoCard::LINE_KINDS)],
            'mode' => ['sometimes', 'in:SOLO,TEAM'],
            'dice_roll_limit' => ['nullable', 'integer', 'min:1'],
            'is_listed' => ['sometimes', 'boolean'],
            'access_mode' => ['sometimes', 'in:OPEN,GUILD,INVITE'],
            'required_guild_id' => ['nullable', 'string', 'required_if:access_mode,GUILD'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['uuid', 'exists:users,id'],
            // Not a plain `url` rule: the app POSTs to whatever this says, so
            // anything accepted here is a request the server will make on a
            // host's say-so. Only Discord's own webhook endpoints — the same
            // check the announcer repeats before every post.
            'discord_webhook_url' => ['sometimes', 'nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if (filled($value) && ! DiscordAnnouncer::isValidUrl($value)) {
                    $fail(trans('validation.discord_webhook_invalid'));
                }
            }],
        ]);

        // Outside the transaction and before it, because it can refuse: a
        // shrink that would delete somebody's completions is rejected as a
        // validation error rather than half-applied alongside the rest.
        if ($event->type === 'BINGO' && $event->bingoCard) {
            $cardChanges = collect($data)
                ->only(['bingo_size', 'win_condition', 'line_bonus', 'requires_approval', 'win_lines'])
                // Nulls are "not submitted for this type", not "clear it" —
                // see the nullable note on the rules above.
                ->reject(fn ($value) => $value === null)
                ->mapWithKeys(fn ($value, $key) => [$key === 'bingo_size' ? 'size' : $key => $value])
                ->all();

            if ($cardChanges !== [] && ! app(BingoService::class)->applyCardSettings($event->bingoCard, $cardChanges)) {
                throw ValidationException::withMessages(['bingo_size' => trans('bingo.cannot_shrink')]);
            }
        }

        DB::transaction(function () use ($data, $event) {
            if (array_key_exists('author_ids', $data)) {
                $ownerIds = $event->authors()->where('is_owner', true)->pluck('user_id');

                $event->authors()->where('is_owner', false)->delete();

                $newNonOwnerIds = collect($data['author_ids'])->diff($ownerIds);
                if ($newNonOwnerIds->isNotEmpty()) {
                    BoardAuthor::insertOrIgnore($newNonOwnerIds->map(fn ($id) => [
                        'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $id, 'is_owner' => false,
                    ])->all());
                }
            }

            // Before the write, so `isDirty` compares against what is
            // currently stored rather than against what we just saved.
            $event->fill(collect($data)->only(self::EVENT_FIELDS)->toArray());

            // A standing is a measurement over a window. Move a date or the
            // metric and every row is still displayed, still ranked, and no
            // longer true — so the event is marked stale and the race page
            // says so until somebody pulls fresh numbers. Not re-synced here:
            // a forty-entrant race is forty outbound requests to somebody
            // else's API, which a form submit does not get to decide.
            if ($event->needsMetric() && $event->isDirty(Event::MEASUREMENT_FIELDS)) {
                $event->standings_stale_since = now();
            }

            $event->save();

            // Same null-means-absent rule as the card fields above: a bingo
            // event's form submits `size` (the S&L enum) as null.
            $boardChanges = collect($data)->only(self::BOARD_FIELDS)
                ->reject(fn ($value, $key) => $value === null && $key === 'size')
                ->toArray();
            if ($boardChanges !== [] && $event->board) {
                $event->board->update($boardChanges);
            }
        });

        return back()->with('board-save', trans('admin.board_updated'));
    }

    /**
     * Put the event on hold, or take it off hold again.
     *
     * Its own endpoint rather than a field on update(), for the same reason
     * `paused_at` is not fillable: this is an announcement, not a setting. It
     * mails everybody who joined, it writes an audit entry, and it is the one
     * button in the settings modal whose effect is felt by other people
     * immediately — none of which belongs riding along inside a save that
     * also renamed the event.
     *
     * A host action, not an owner one: anyone trusted to edit the event is
     * trusted to stop it, and the person who notices the problem at 2am is
     * rarely the person whose name is on it.
     */
    public function pause(
        Request $request,
        Event $event,
        EventNotificationService $notifier,
        bool $asAdmin = false,
    ): RedirectResponse {
        $this->assertCanEditEvent($request->user(), $event, $asAdmin);

        $data = $request->validate([
            'paused' => ['required', 'boolean'],
            // Opt-out, not opt-in. The default is the behaviour that serves
            // the players — being told the event they are in has stopped —
            // and a host who is only flipping this to fix a typo in the
            // rules can uncheck it.
            'notify' => ['sometimes', 'boolean'],
            // One line, and only on the way in. "Paused" says a claim will
            // not be accepted; it does not say "we are waiting on a
            // screenshot from team B", which is the thing the clan is
            // actually asking in Discord. Capped at a sentence because it
            // renders on a banner and inside an email.
            'reason' => ['sometimes', 'nullable', 'string', 'max:200'],
        ]);

        $paused = (bool) $data['paused'];

        // Idempotent, and quietly so: a double-click must not send a second
        // round of mail to everyone.
        if ($paused === $event->isPaused()) {
            return back();
        }

        $event->forceFill([
            'paused_at' => $paused ? now() : null,
            // Cleared on resume rather than kept as history: it describes why
            // the event is stopped right now, and a stale reason on a running
            // event would be worse than none.
            'pause_reason' => $paused ? ($data['reason'] ?? null) : null,
        ])->save();

        AuditLog::record($paused ? 'event.paused' : 'event.resumed', $event);

        $message = trans($paused ? 'events.paused_confirmed' : 'events.resumed_confirmed');

        if ($data['notify'] ?? true) {
            $counts = $notifier->announce(
                $event,
                $paused ? EventStatusChanged::PAUSED : EventStatusChanged::RESUMED,
                $request->user(),
            );

            $message .= ' '.$this->notifiedSummary($counts);
        }

        return back()->with('board-save', $message);
    }

    /**
     * Soft-deleted, and announced first.
     *
     * The announcement has to happen before the delete: it reads the
     * participant list and the title off a row that is about to disappear
     * from every default query, and the mail is queued, so by the time it
     * sends there is nothing to look up. Same reason the audit entry goes
     * first — AuditLog::record() resolves the label from the live model.
     */
    /**
     * The way in for an admin who has no other one.
     *
     * On the public side an admin is an ordinary user, so on an event they
     * do not host there are no host controls at all — correct, and until now
     * silent: nothing on the page said the power exists elsewhere, so it
     * read as missing buttons. Reported exactly that way.
     *
     * Null for everybody else, including an admin who DOES host the event —
     * they already have the ordinary controls and do not need to reach for
     * the admin ones.
     */
    private function adminEditUrl(?User $user, Event $event): ?string
    {
        if ($user === null || ! $user->isAdmin() || $user->canEditEvent($event)) {
            return null;
        }

        return route('admin.events').'?event='.$event->id;
    }

    public function destroy(
        Request $request,
        Event $event,
        EventNotificationService $notifier,
        bool $asAdmin = false,
    ): RedirectResponse {
        $this->assertOwnsEvent($request->user(), $event, $asAdmin);

        $notify = $request->boolean('notify', true);

        $counts = $notify
            ? $notifier->announce($event, EventStatusChanged::CANCELLED, $request->user())
            : null;

        AuditLog::record('event.deleted', $event, ['participants' => $event->participants()->count()]);

        $event->delete();

        $message = trans('admin.board_deleted');

        if ($counts !== null) {
            $message .= ' '.$this->notifiedSummary($counts);
        }

        // A host goes back to the events hub, because the page they were on
        // no longer exists. An admin stays in the admin list — that is where
        // they were working, it is the only place the deleted event is still
        // visible, and it is where they would go to put it back.
        return $asAdmin
            ? back()->with('board-save', $message)
            : redirect()->route('events.index')->with('board-save', $message);
    }

    /**
     * How many of the people who joined could actually be reached.
     *
     * Said out loud rather than left to be assumed, because the answer is
     * usually "about half": Discord login never asks for an email address
     * (see EventNotificationService), so a host announcing a cancellation to
     * thirty players is often announcing it to fourteen.
     *
     * @param  array{sent: int, total: int}  $counts
     */
    private function notifiedSummary(array $counts): string
    {
        // Said first when it happened, because it is the line that reaches
        // everybody: a clan's Discord channel has the players the mail does
        // not. Only mentioned when a webhook is actually configured, so an
        // event without one does not read as having failed to post.
        $discord = ($counts['discord'] ?? false) ? ' '.trans('events.notified_discord') : '';

        if ($counts['total'] === 0) {
            return trans('events.notified_nobody_joined').$discord;
        }

        if ($counts['sent'] === 0) {
            return trans('events.notified_none', ['total' => $counts['total']]).$discord;
        }

        return trans('events.notified_some', ['sent' => $counts['sent'], 'total' => $counts['total']]).$discord;
    }

    /**
     * JSON, for BoardSettingsModal's Teams tab (same fetch()-not-Inertia
     * pattern as invites — the modal isn't a page component). Returns both
     * the board's currently-assigned teams and the set of other teams the
     * current user could add, using the same guild-based visibility rule as
     * TeamController::index().
     */
    public function teamsIndex(Request $request, Event $event, bool $asAdmin = false): JsonResponse
    {
        $this->assertCanEditEvent($request->user(), $event, $asAdmin);

        $assignedTeamIds = $event->eventTeams()->pluck('team_id');

        return response()->json([
            // Already assigned, so already this event's business — listed
            // whether or not the viewer could otherwise see them, or a host
            // could not remove a team somebody else attached.
            'assigned' => $event->eventTeams()->with('team')->get()->pluck('team'),
            'available' => Team::query()
                ->visibleTo($request->user())
                ->whereNotIn('id', $assignedTeamIds)
                ->orderBy('name')
                ->get(['id', 'name', 'guild_name']),
        ]);
    }

    /** Ported from BoardsService::addTeamToBoard() — idempotent (upsert). */
    public function addTeam(Request $request, Event $event, bool $asAdmin = false): RedirectResponse
    {
        $this->assertCanEditEvent($request->user(), $event, $asAdmin);

        $data = $request->validate(['team_id' => ['required', 'uuid', 'exists:teams,id']]);

        BoardTeam::firstOrCreate(
            ['event_id' => $event->id, 'team_id' => $data['team_id']],
            ['id' => (string) str()->uuid()],
        );

        // Target is the board, scope is the team — so this shows up both
        // under the board and when filtering the team's own clan.
        AuditLog::record('board.team_added', $event, [], Team::find($data['team_id']));

        return back()->with('board-save', trans('admin.team_added'));
    }

    /** Ported from BoardsService::removeTeamFromBoard(). */
    public function removeTeam(Event $event, Team $team, bool $asAdmin = false): RedirectResponse
    {
        $this->assertCanEditEvent(Auth::user(), $event, $asAdmin);

        BoardTeam::where('event_id', $event->id)->where('team_id', $team->id)->delete();

        AuditLog::record('board.team_removed', $event, [], $team);

        return back()->with('board-save', trans('admin.team_removed'));
    }

    /**
     * The Discord servers this account is in, for the GUILD access picker.
     *
     * The form used to ask for the server id as free text — an 18-digit
     * snowflake nobody knows by heart, for a value already synced on every
     * Discord login. JSON over fetch() rather than an Inertia prop because
     * only one tab of one modal ever needs it.
     *
     * Ordered by what this account has actually used, most recent first, then
     * alphabetically for the rest — a Discord account in thirty servers gets
     * an alphabetical list where the two servers they run events in are
     * wherever the alphabet put them. See guildRecency().
     *
     * `icon_url` is built here rather than in the three components that render
     * this list, because the CDN path is a fact about Discord and not about
     * any one picker. `guild_icon` stores the hash Discord's guild payload
     * carries, not a URL — an animated icon is prefixed `a_` and served as a
     * gif, everything else as a png.
     */
    public function myGuilds(Request $request): JsonResponse
    {
        $recency = $this->guildRecency($request->user()->id);

        // Used servers first, most recent at the top; the untouched remainder
        // keeps the alphabetical order the query already gave it.
        [$used, $untouched] = UserGuild::where('user_id', $request->user()->id)
            ->orderBy('guild_name')
            ->get(['guild_id', 'guild_name', 'guild_icon'])
            ->partition(fn (UserGuild $guild) => isset($recency[$guild->guild_id]));

        $guilds = $used
            ->sortByDesc(fn (UserGuild $guild) => $recency[$guild->guild_id])
            ->concat($untouched)
            ->values()
            ->map(fn (UserGuild $guild) => [
                'id' => $guild->guild_id,
                'name' => $guild->guild_name,
                'icon' => $guild->guild_icon,
                'icon_url' => $this->guildIconUrl($guild->guild_id, $guild->guild_icon),
                'used_at' => $recency[$guild->guild_id] ?? null,
            ]);

        return response()->json(['guilds' => $guilds]);
    }

    /**
     * When this account last did something with each Discord server, as a
     * timestamp string per guild id.
     *
     * Three separate signals rather than one, because "used" has three
     * shapes here and no single table holds them: a team you are in, an event
     * you author, and a blueprint you saved for a server. Kept as three cheap
     * keyed lookups merged in PHP instead of a union query — the row counts
     * are per-user and small, and the merge rule (take the latest) is clearer
     * written out than expressed in SQL.
     *
     * @return array<string, string>
     */
    private function guildRecency(string $userId): array
    {
        $sources = [
            // Teams this account belongs to, dated from when it joined.
            DB::table('team_members')
                ->join('teams', 'teams.id', '=', 'team_members.team_id')
                ->where('team_members.user_id', $userId)
                ->whereNotNull('teams.guild_id')
                ->pluck('team_members.created_at', 'teams.guild_id'),

            // Events this account authors, dated from when they were created.
            DB::table('board_authors')
                ->join('events', 'events.id', '=', 'board_authors.event_id')
                ->where('board_authors.user_id', $userId)
                ->whereNotNull('events.required_guild_id')
                ->pluck('events.created_at', 'events.required_guild_id'),

            // Blueprints this account saved for a specific server.
            DB::table('event_blueprints')
                ->where('created_by', $userId)
                ->whereNotNull('guild_id')
                ->pluck('created_at', 'guild_id'),
        ];

        $recency = [];

        foreach ($sources as $source) {
            foreach ($source as $guildId => $usedAt) {
                // pluck() keyed by a column keeps only the last row per key,
                // so each source has already collapsed itself; this only has
                // to pick a winner across the three.
                if ($usedAt !== null && (! isset($recency[$guildId]) || $usedAt > $recency[$guildId])) {
                    $recency[$guildId] = (string) $usedAt;
                }
            }
        }

        return $recency;
    }

    /**
     * Discord's CDN path for a guild icon, or null when the server has none
     * (a real and common state — a guild icon is optional).
     */
    private function guildIconUrl(string $guildId, ?string $hash): ?string
    {
        if ($hash === null || $hash === '') {
            return null;
        }

        $extension = str_starts_with($hash, 'a_') ? 'gif' : 'png';

        return "https://cdn.discordapp.com/icons/{$guildId}/{$hash}.{$extension}?size=64";
    }
}
