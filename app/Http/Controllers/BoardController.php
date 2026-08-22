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
use App\Models\UserGuild;
use App\Services\BingoService;
use App\Services\BoardAccessService;
use App\Services\EventParticipationService;
use App\Services\EventStandingsService;
use App\Services\PlayerBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    private const EVENT_FIELDS = ['title', 'type', 'metric', 'description', 'mode', 'access_mode', 'required_guild_id', 'is_listed', 'start_date', 'end_date'];

    private const BOARD_FIELDS = ['size', 'dice_roll_limit'];

    /**
     * The author's user is loaded by column, not whole.
     *
     * `authors` goes to the browser as-is from cardData(), and `User` marks
     * only password and remember_token hidden — so a bare `authors.user`
     * published every host's email address on every event page and every
     * board card. Naming the columns here fixes it for every caller at once;
     * the pages only ever read the name and the avatar.
     */
    private const EVENT_WITH = ['authors.user:id,discord_username,nickname,avatar_url', 'eventTeams.team', 'board', 'bingoCard'];

    /**
     * Flattens an event and its board into the shape the cards render.
     *
     * A view model rather than the raw models: the split put size and the
     * dice limit on the board and everything else on the event, and pushing
     * that seam into every template would mean the UI has to know which half
     * a field lives in just to display it. `id` is deliberately the EVENT's —
     * that is what the URLs address.
     */
    private function cardData(Event $event): array
    {
        return [
            ...$event->only(['id', 'title', 'type', 'metric', 'description', 'mode', 'access_mode', 'is_listed', 'start_date', 'end_date']),
            'size' => $event->board?->size,
            'dice_roll_limit' => $event->board?->dice_roll_limit,
            // Bingo's grid is a side length, not a size enum — a separate
            // field so a card never has to guess which kind of grid it holds.
            'bingo_size' => $event->bingoCard?->size,
            // The whole card, for the settings modal's Format tab. Nested
            // rather than flattened alongside bingo_size because everything
            // in here belongs to the card and nothing else reads it — the
            // modal picks it apart in cardFields().
            'card' => $event->bingoCard ? [
                'size' => $event->bingoCard->size,
                'winCondition' => $event->bingoCard->win_condition,
                'lineBonus' => $event->bingoCard->line_bonus,
                'requiresApproval' => $event->bingoCard->requires_approval,
                'winLines' => $event->bingoCard->winLines(),
            ] : null,
            'authors' => $event->authors,
        ];
    }

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
            ->map(fn (Event $event) => $this->cardData($event));

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
            ->map(fn (Event $event) => $this->cardData($event))
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
            ->map(fn (Event $event) => $this->cardData($event));

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
                'board' => $this->cardData($event),
                'isHost' => $event->authors->contains(fn ($a) => $a->user_id === $user->id),
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
    public function show(Event $event, BoardAccessService $access, PlayerBoardService $playerBoards): Response
    {
        $user = Auth::user();

        if (! $access->hasAccess($user, $event)) {
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
        $playerBoard = $playerBoards->find($event, $user)?->load('completedTiles:id,player_board_id,tile_id');

        // Every player/team on the board with their current position — feeds
        // BoardShow.vue's "show other players" avatar stacks on tiles and the
        // sidebar's mini leaderboard preview. pathHasSnake/pathHasLadder mirror
        // LeaderboardController's same computation (kept duplicated rather than
        // extracted — it's a handful of lines with exactly two call sites).
        $tiles = $event->board?->tiles ?? collect();
        $maxPosition = $tiles->count() - 1;
        $players = $event->playerBoards()
            ->with(['user:id,discord_username,nickname,avatar_url', 'team:id,name,icon_url'])
            ->orderByDesc('player_boards.current_position')
            // Qualified: playerBoards() is a hasManyThrough, so the join
            // brings boards' own id into scope and bare names are ambiguous.
            ->get(['player_boards.id', 'player_boards.user_id', 'player_boards.team_id', 'player_boards.current_position'])
            ->map(function ($pb) use ($tiles, $maxPosition) {
                $pathTiles = $tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);

                return [
                    ...$pb->only(['id', 'user_id', 'team_id', 'current_position']),
                    'user' => $pb->user,
                    'team' => $pb->team,
                    'tilesRemaining' => $maxPosition - $pb->current_position,
                    'pathHasLadder' => $pathTiles->contains(fn ($t) => $t->type === 'LADDER' && $t->target_position !== null),
                    'pathHasSnake' => $pathTiles->contains(fn ($t) => $t->type === 'SNAKE' && $t->target_position !== null),
                ];
            });

        return Inertia::render('BoardShow', [
            // Flattened for the same reason the cards are — see cardData().
            'board' => $this->cardData($event),
            'tiles' => $tiles,
            'playerBoard' => $playerBoard === null ? null : [
                ...$playerBoard->only(['id', 'current_position', 'dice_rolls_today']),
                'completedTileIds' => $playerBoard->completedTiles->pluck('tile_id'),
            ],
            'players' => $players,
            // TEAM boards where the user isn't on any assigned team can't
            // play at all — BoardShow.vue renders a dedicated "no team on
            // this board" empty state instead of the grid for this case.
            'hasTeam' => $playerBoards->hasTeam($event, $user),
            'joined' => app(EventParticipationService::class)->has($user, $event),
            'canEdit' => $user->canEditEvent($event),
        ]);
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

        $card->load('squares.task:id,title,icon_url');

        $user = Auth::user();
        $canEdit = $user->canEditEvent($event);
        $competitor = $bingo->competitorFor($event, $user);

        // Every claim this viewer has made, whatever its state — they need to
        // see their own pending square sitting in the queue, or they will
        // claim it again.
        $claims = $competitor === null ? collect() : $bingo->claimsFor($card, $competitor);
        $approved = $claims->filter->isApproved()->keys()->map(fn ($p) => (int) $p)->all();

        return Inertia::render('Events/Bingo', [
            'event' => $this->cardData($event),
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
            'approvedBy' => $bingo->approvedBy($card),
            'completedLines' => $bingo->completedLines($card->size, $approved, $card->winLines()),
            'hasWon' => $bingo->hasWon($card, $approved),
            'canPlay' => $competitor !== null,
            'joined' => app(EventParticipationService::class)->has($user, $event),
            'standings' => $bingo->standings($event, $card),
            // Hosts get the review queue on the same page as the card — a
            // separate screen for it would mean leaving the thing you are
            // judging to judge it.
            'pending' => $canEdit ? $bingo->pendingQueue($card) : [],
            'canEdit' => $canEdit,
        ]);
    }

    private function showMetricRace(Event $event, EventStandingsService $standings): Response
    {
        $user = Auth::user();

        return Inertia::render('Events/SkillRace', [
            'event' => [
                ...$this->cardData($event),
                'metric' => $event->metric,
                // 'skill' or 'boss' — decides whether the page counts XP or
                // kills, and which i18n namespace the metric name comes from.
                'metricKind' => $event->metricKind(),
            ],
            // The initial paint. From here the SSE stream owns the table, so
            // this is a snapshot rather than the only delivery — and it means
            // the page is complete before any JavaScript runs, which matters
            // for SSR and for anyone the stream never reaches.
            'standings' => $standings->forEvent($event),
            // Nothing to rank without an RSN — the hiscores are keyed by it.
            // The page prompts for one instead of silently leaving someone off
            // a leaderboard they think they entered.
            'osrsUsername' => $user?->osrs_username,
            'isParticipant' => app(EventParticipationService::class)->has($user, $event)
                || $event->standings()->where('user_id', $user?->id)->exists(),
            'canEdit' => $user?->canEditEvent($event) ?? false,
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
            $participation->join($request->user(), $event, $token);
        } catch (ValidationException $e) {
            return redirect()->route('events.show', $event)->with('board-save-error', collect($e->errors())->flatten()->first() ?? 'Could not join this board.');
        }

        return redirect()->route('events.show', $event)->with('board-save', trans('board.joined'));
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

            $event->update(collect($data)->only(self::EVENT_FIELDS)->toArray());

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

    public function destroy(Event $event, bool $asAdmin = false): RedirectResponse
    {
        $this->assertOwnsEvent(Auth::user(), $event, $asAdmin);

        $event->delete();

        return redirect()->route('events.index')->with('board-save', trans('admin.board_deleted'));
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
     */
    public function myGuilds(Request $request): JsonResponse
    {
        $guilds = UserGuild::where('user_id', $request->user()->id)
            ->orderBy('guild_name')
            ->get(['guild_id', 'guild_name', 'guild_icon'])
            ->map(fn (UserGuild $guild) => [
                'id' => $guild->guild_id,
                'name' => $guild->guild_name,
                'icon' => $guild->guild_icon,
            ]);

        return response()->json(['guilds' => $guilds]);
    }
}
