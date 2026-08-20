<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Board;
use App\Models\Event;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\Team;
use App\Models\UserGuild;
use App\Services\BoardAccessService;
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

    private const EVENT_FIELDS = ['title', 'type', 'description', 'mode', 'access_mode', 'required_guild_id', 'is_listed', 'start_date', 'end_date'];

    private const BOARD_FIELDS = ['size', 'dice_roll_limit'];

    private const EVENT_WITH = ['authors.user', 'eventTeams.team', 'board'];

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
            ...$event->only(['id', 'title', 'type', 'description', 'mode', 'access_mode', 'is_listed', 'start_date', 'end_date']),
            'size' => $event->board?->size,
            'dice_roll_limit' => $event->board?->dice_roll_limit,
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

        // A slice of what you're playing, so the hub has something to say to
        // someone who already has events — /my-events existed but nothing
        // linked to it, which is how it stayed invisible.
        $mine = collect();
        if ($user = Auth::user()) {
            $mine = $user->playerBoards()
                ->with(['board.event.authors.user', 'board.event.eventTeams.team'])
                ->get()
                ->filter(fn ($pb) => $pb->board?->event !== null)
                ->sortByDesc(fn ($pb) => $pb->board->event->start_date)
                ->take(self::HUB_SLICE)
                ->map(fn ($pb) => $this->cardData($pb->board->event))
                ->values();
        }

        return Inertia::render('Boards/Index', [
            // The full list stays — the hub renders a slice of it and the
            // "view all" page renders the rest from the same prop.
            'boards' => $events,
            'mine' => $mine,
            'mineTotal' => Auth::user()?->playerBoards()->count() ?? 0,
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
    public function mine(): Response
    {
        $tileCounts = ['SIZE_5X5' => 25, 'SIZE_7X7' => 49, 'SIZE_9X9' => 81];

        $boards = Auth::user()->playerBoards()
            ->with(['board.event.authors.user', 'board.event.eventTeams.team', 'completedTiles', 'board.tiles:id,board_id,position,type'])
            ->get()
            ->filter(fn ($pb) => $pb->board?->event !== null)
            ->sortByDesc(fn ($pb) => $pb->board->event->start_date)
            ->values()
            ->map(function ($pb) use ($tileCounts) {
                $total = $tileCounts[$pb->board->size] ?? 49;
                $position = max(0, $pb->current_position);

                return [
                    'board' => $this->cardData($pb->board->event),
                    // Enough to draw the board's shape without shipping every
                    // tile's task and description: only the tiles that aren't
                    // plain, plus where this player stands.
                    'preview' => [
                        'size' => $pb->board->size,
                        'specialTiles' => $pb->board->tiles
                            ->filter(fn ($t) => $t->type !== 'NORMAL')
                            ->map(fn ($t) => ['position' => $t->position, 'type' => $t->type])
                            ->values(),
                        'currentPosition' => max(0, $pb->current_position),
                        'completedPositions' => $pb->board->tiles
                            ->whereIn('id', $pb->completedTiles->pluck('tile_id'))
                            ->pluck('position')
                            ->values(),
                    ],
                    'progress' => [
                        'current' => $position + 1,
                        'total' => $total,
                        // Capped at 99 until the final tile is actually
                        // completed — same rule the profile page uses, so a
                        // board in progress never reads as finished.
                        'pct' => $total <= 1 ? 0 : min(99, (int) floor(($position / ($total - 1)) * 100)),
                    ],
                ];
            });

        return Inertia::render('Boards/Mine', ['boards' => $boards]);
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

        // Ported from the old PlayersService.findPlayerBoard(): once access is
        // confirmed (we're past the access-gate check above), the PlayerBoard
        // is created here on page view, not lazily on the first roll/toggle.
        // An earlier version of this method used a pure find() instead,
        // reasoned as matching SOLO's cold-start behavior — it didn't: the old
        // app always auto-creates on confirmed access, which is what makes a
        // brand-new visitor immediately see "Your current task" (tile 1) and
        // the completion-gated dice roller instead of an empty sidebar.
        $playerBoard = $playerBoards->getOrCreate($event, $user)?->load('completedTiles:id,player_board_id,tile_id');

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
            'canEdit' => $user->canEditEvent($event),
        ]);
    }

    /** Ported from AccessService::joinBoard() / InvitesService::useInvite(). */
    public function join(Request $request, Event $event, BoardAccessService $access): RedirectResponse
    {
        $data = $request->validate(['token_or_code' => ['nullable', 'string']]);

        try {
            $access->joinBoard($request->user(), $event, $data['token_or_code'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('events.show', $event)->with('board-save', 'Joined the board.');
    }

    /**
     * Direct invite-link join (GET /boards/{board}/join/{token}) — ported
     * from the old join/[token].vue. Unauthenticated visitors go through
     * Discord login first; redirect()->intended() (Laravel's own mechanism,
     * not a hand-rolled localStorage flag like the old client-side version
     * needed) brings them right back here afterward.
     */
    public function joinByLink(Request $request, Event $event, string $token, BoardAccessService $access): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        try {
            $access->joinBoard($request->user(), $event, $token);
        } catch (ValidationException $e) {
            return redirect()->route('events.show', $event)->with('board-save-error', $e->errors()['access'][0] ?? 'Could not join this board.');
        }

        return redirect()->route('events.show', $event)->with('board-save', 'Joined the board.');
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            // Only creatable types — a planned one is advertised in the UI
            // as coming soon, which is not the same as being selectable.
            'type' => ['nullable', Rule::in(Event::availableTypes())],
            'size' => ['required', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            'mode' => ['nullable', 'in:SOLO,TEAM'],
            'dice_roll_limit' => ['nullable', 'integer', 'min:1'],
            'is_listed' => ['nullable', 'boolean'],
            'access_mode' => ['nullable', 'in:OPEN,GUILD,INVITE'],
            'required_guild_id' => ['nullable', 'string'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['uuid', 'exists:users,id'],
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

            $event->board()->create([
                'id' => (string) str()->uuid(),
                ...collect($data)->only(self::BOARD_FIELDS)->toArray(),
            ]);

            $extraAuthorIds = collect($data['author_ids'] ?? [])
                ->reject(fn ($id) => $id === $request->user()->id)
                ->unique();

            BoardAuthor::insert([
                ['id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $request->user()->id, 'is_owner' => true],
                ...$extraAuthorIds->map(fn ($id) => [
                    'id' => (string) str()->uuid(), 'event_id' => $event->id, 'user_id' => $id, 'is_owner' => false,
                ])->all(),
            ]);

            return $board;
        });

        return redirect()->route('events.show', $event)->with('board-save', 'Board created.');
    }

    /**
     * Ported from BoardsService::update() — author sync preserves existing
     * owners (they can't be replaced via this bulk update), same as the
     * original transaction: delete all non-owner authors, recreate the
     * submitted set minus anyone who's already an owner.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->canEditEvent($event), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'type' => ['sometimes', Rule::in(Event::availableTypes())],
            'size' => ['sometimes', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            'mode' => ['sometimes', 'in:SOLO,TEAM'],
            'dice_roll_limit' => ['nullable', 'integer', 'min:1'],
            'is_listed' => ['sometimes', 'boolean'],
            'access_mode' => ['sometimes', 'in:OPEN,GUILD,INVITE'],
            'required_guild_id' => ['nullable', 'string'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['uuid', 'exists:users,id'],
        ]);

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

            $boardChanges = collect($data)->only(self::BOARD_FIELDS)->toArray();
            if ($boardChanges !== [] && $event->board) {
                $event->board->update($boardChanges);
            }
        });

        return back()->with('board-save', 'Board updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin() || $event->authors()->where(['user_id' => Auth::id(), 'is_owner' => true])->exists(), 403);

        $event->delete();

        return redirect()->route('events.index')->with('board-save', 'Board deleted.');
    }

    /**
     * JSON, for BoardSettingsModal's Teams tab (same fetch()-not-Inertia
     * pattern as invites — the modal isn't a page component). Returns both
     * the board's currently-assigned teams and the set of other teams the
     * current user could add, using the same guild-based visibility rule as
     * TeamController::index().
     */
    public function teamsIndex(Request $request, Event $event): JsonResponse
    {
        abort_unless($request->user()->canEditEvent($event), 403);

        $assignedTeamIds = $event->boardTeams()->pluck('team_id');

        $availableQuery = Team::query()->orderBy('name');
        if (! $request->user()->isAdmin()) {
            $guildIds = UserGuild::where('user_id', $request->user()->id)->pluck('guild_id');
            $availableQuery->where(fn ($q) => $q->whereNull('guild_id')->orWhereIn('guild_id', $guildIds));
        }

        return response()->json([
            'assigned' => $event->boardTeams()->with('team')->get()->pluck('team'),
            'available' => $availableQuery->whereNotIn('id', $assignedTeamIds)->get(['id', 'name']),
        ]);
    }

    /** Ported from BoardsService::addTeamToBoard() — idempotent (upsert). */
    public function addTeam(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->canEditEvent($event), 403);

        $data = $request->validate(['team_id' => ['required', 'uuid', 'exists:teams,id']]);

        BoardTeam::firstOrCreate(
            ['event_id' => $event->id, 'team_id' => $data['team_id']],
            ['id' => (string) str()->uuid()],
        );

        // Target is the board, scope is the team — so this shows up both
        // under the board and when filtering the team's own clan.
        AuditLog::record('board.team_added', $event, [], Team::find($data['team_id']));

        return back()->with('board-save', 'Team added to board.');
    }

    /** Ported from BoardsService::removeTeamFromBoard(). */
    public function removeTeam(Event $event, Team $team): RedirectResponse
    {
        abort_unless(Auth::user()->canEditEvent($event), 403);

        BoardTeam::where('board_id', $event->id)->where('team_id', $team->id)->delete();

        AuditLog::record('board.team_removed', $event, [], $team);

        return back()->with('board-save', 'Team removed from board.');
    }
}
