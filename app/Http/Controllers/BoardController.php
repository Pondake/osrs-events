<?php

namespace App\Http\Controllers;

use App\Models\Board;
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
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    private const BOARD_WITH = ['authors.user', 'boardTeams.team'];

    /**
     * Public board list — only listed boards, matching the old
     * BoardsService::findAll(). Deliberately reachable without auth: it's
     * the page search engines index, so it can't sit behind a login.
     * Admins see unlisted ones too via Settings\Admin\BoardController.
     */
    public function index(): Response
    {
        $boards = Board::where('is_listed', true)
            ->with(self::BOARD_WITH)
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Boards/Index', ['boards' => $boards]);
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
            ->with(['board.authors.user', 'board.boardTeams.team'])
            ->get()
            ->filter(fn ($pb) => $pb->board !== null)
            ->sortByDesc(fn ($pb) => $pb->board->start_date)
            ->values()
            ->map(function ($pb) use ($tileCounts) {
                $total = $tileCounts[$pb->board->size] ?? 49;
                $position = max(0, $pb->current_position);

                return [
                    'board' => $pb->board,
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
    public function show(Board $board, BoardAccessService $access, PlayerBoardService $playerBoards): Response
    {
        $user = Auth::user();

        if (! $access->hasAccess($user, $board)) {
            $canJoin = $access->canJoin($user, $board);

            return Inertia::render('Boards/AccessGate', [
                'board' => $board->only(['id', 'title', 'access_mode']),
                'reason' => $canJoin['reason'] ?? null,
                'canRequestInvite' => $board->access_mode === 'INVITE',
            ]);
        }

        $board->load([...self::BOARD_WITH, 'tiles.task']);

        // Ported from the old PlayersService.findPlayerBoard(): once access is
        // confirmed (we're past the access-gate check above), the PlayerBoard
        // is created here on page view, not lazily on the first roll/toggle.
        // An earlier version of this method used a pure find() instead,
        // reasoned as matching SOLO's cold-start behavior — it didn't: the old
        // app always auto-creates on confirmed access, which is what makes a
        // brand-new visitor immediately see "Your current task" (tile 1) and
        // the completion-gated dice roller instead of an empty sidebar.
        $playerBoard = $playerBoards->getOrCreate($board, $user)?->load('completedTiles:id,player_board_id,tile_id');

        // Every player/team on the board with their current position — feeds
        // BoardShow.vue's "show other players" avatar stacks on tiles and the
        // sidebar's mini leaderboard preview. pathHasSnake/pathHasLadder mirror
        // LeaderboardController's same computation (kept duplicated rather than
        // extracted — it's a handful of lines with exactly two call sites).
        $maxPosition = $board->tiles->count() - 1;
        $players = $board->playerBoards()
            ->with(['user:id,discord_username,nickname,avatar_url', 'team:id,name,icon_url'])
            ->orderByDesc('current_position')
            ->get(['id', 'user_id', 'team_id', 'current_position'])
            ->map(function ($pb) use ($board, $maxPosition) {
                $pathTiles = $board->tiles->filter(fn ($t) => $t->position > $pb->current_position && $t->position <= $maxPosition);

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
            'board' => $board,
            'tiles' => $board->tiles,
            'playerBoard' => $playerBoard === null ? null : [
                ...$playerBoard->only(['id', 'current_position', 'dice_rolls_today']),
                'completedTileIds' => $playerBoard->completedTiles->pluck('tile_id'),
            ],
            'players' => $players,
            // TEAM boards where the user isn't on any assigned team can't
            // play at all — BoardShow.vue renders a dedicated "no team on
            // this board" empty state instead of the grid for this case.
            'hasTeam' => $playerBoards->hasTeam($board, $user),
            'canEdit' => $user->canEditBoard($board),
        ]);
    }

    /** Ported from AccessService::joinBoard() / InvitesService::useInvite(). */
    public function join(Request $request, Board $board, BoardAccessService $access): RedirectResponse
    {
        $data = $request->validate(['token_or_code' => ['nullable', 'string']]);

        try {
            $access->joinBoard($request->user(), $board, $data['token_or_code'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('boards.show', $board)->with('board-save', 'Joined the board.');
    }

    /**
     * Direct invite-link join (GET /boards/{board}/join/{token}) — ported
     * from the old join/[token].vue. Unauthenticated visitors go through
     * Discord login first; redirect()->intended() (Laravel's own mechanism,
     * not a hand-rolled localStorage flag like the old client-side version
     * needed) brings them right back here afterward.
     */
    public function joinByLink(Request $request, Board $board, string $token, BoardAccessService $access): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        try {
            $access->joinBoard($request->user(), $board, $token);
        } catch (ValidationException $e) {
            return redirect()->route('boards.show', $board)->with('board-save-error', $e->errors()['access'][0] ?? 'Could not join this board.');
        }

        return redirect()->route('boards.show', $board)->with('board-save', 'Joined the board.');
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
            'size' => ['required', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            'mode' => ['nullable', 'in:SOLO,TEAM'],
            'dice_roll_limit' => ['nullable', 'integer', 'min:1'],
            'is_listed' => ['nullable', 'boolean'],
            'access_mode' => ['nullable', 'in:OPEN,GUILD,INVITE'],
            'required_guild_id' => ['nullable', 'string'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['uuid', 'exists:users,id'],
        ]);

        $board = DB::transaction(function () use ($data, $request) {
            $board = Board::create([
                'id' => (string) str()->uuid(),
                ...collect($data)->except('author_ids')->toArray(),
                'mode' => $data['mode'] ?? 'SOLO',
                'is_listed' => $data['is_listed'] ?? true,
                'access_mode' => $data['access_mode'] ?? 'OPEN',
            ]);

            $extraAuthorIds = collect($data['author_ids'] ?? [])
                ->reject(fn ($id) => $id === $request->user()->id)
                ->unique();

            BoardAuthor::insert([
                ['id' => (string) str()->uuid(), 'board_id' => $board->id, 'user_id' => $request->user()->id, 'is_owner' => true],
                ...$extraAuthorIds->map(fn ($id) => [
                    'id' => (string) str()->uuid(), 'board_id' => $board->id, 'user_id' => $id, 'is_owner' => false,
                ])->all(),
            ]);

            return $board;
        });

        return redirect()->route('boards.show', $board)->with('board-save', 'Board created.');
    }

    /**
     * Ported from BoardsService::update() — author sync preserves existing
     * owners (they can't be replaced via this bulk update), same as the
     * original transaction: delete all non-owner authors, recreate the
     * submitted set minus anyone who's already an owner.
     */
    public function update(Request $request, Board $board): RedirectResponse
    {
        abort_unless($request->user()->canEditBoard($board), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'size' => ['sometimes', 'in:SIZE_5X5,SIZE_7X7,SIZE_9X9'],
            'mode' => ['sometimes', 'in:SOLO,TEAM'],
            'dice_roll_limit' => ['nullable', 'integer', 'min:1'],
            'is_listed' => ['sometimes', 'boolean'],
            'access_mode' => ['sometimes', 'in:OPEN,GUILD,INVITE'],
            'required_guild_id' => ['nullable', 'string'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['uuid', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($data, $board) {
            if (array_key_exists('author_ids', $data)) {
                $ownerIds = $board->authors()->where('is_owner', true)->pluck('user_id');

                $board->authors()->where('is_owner', false)->delete();

                $newNonOwnerIds = collect($data['author_ids'])->diff($ownerIds);
                if ($newNonOwnerIds->isNotEmpty()) {
                    BoardAuthor::insertOrIgnore($newNonOwnerIds->map(fn ($id) => [
                        'id' => (string) str()->uuid(), 'board_id' => $board->id, 'user_id' => $id, 'is_owner' => false,
                    ])->all());
                }
            }

            $board->update(collect($data)->except('author_ids')->toArray());
        });

        return back()->with('board-save', 'Board updated.');
    }

    public function destroy(Board $board): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin() || $board->authors()->where(['user_id' => Auth::id(), 'is_owner' => true])->exists(), 403);

        $board->delete();

        return redirect()->route('boards.index')->with('board-save', 'Board deleted.');
    }

    /**
     * JSON, for BoardSettingsModal's Teams tab (same fetch()-not-Inertia
     * pattern as invites — the modal isn't a page component). Returns both
     * the board's currently-assigned teams and the set of other teams the
     * current user could add, using the same guild-based visibility rule as
     * TeamController::index().
     */
    public function teamsIndex(Request $request, Board $board): JsonResponse
    {
        abort_unless($request->user()->canEditBoard($board), 403);

        $assignedTeamIds = $board->boardTeams()->pluck('team_id');

        $availableQuery = Team::query()->orderBy('name');
        if (! $request->user()->isAdmin()) {
            $guildIds = UserGuild::where('user_id', $request->user()->id)->pluck('guild_id');
            $availableQuery->where(fn ($q) => $q->whereNull('guild_id')->orWhereIn('guild_id', $guildIds));
        }

        return response()->json([
            'assigned' => $board->boardTeams()->with('team')->get()->pluck('team'),
            'available' => $availableQuery->whereNotIn('id', $assignedTeamIds)->get(['id', 'name']),
        ]);
    }

    /** Ported from BoardsService::addTeamToBoard() — idempotent (upsert). */
    public function addTeam(Request $request, Board $board): RedirectResponse
    {
        abort_unless($request->user()->canEditBoard($board), 403);

        $data = $request->validate(['team_id' => ['required', 'uuid', 'exists:teams,id']]);

        BoardTeam::firstOrCreate(
            ['board_id' => $board->id, 'team_id' => $data['team_id']],
            ['id' => (string) str()->uuid()],
        );

        return back()->with('board-save', 'Team added to board.');
    }

    /** Ported from BoardsService::removeTeamFromBoard(). */
    public function removeTeam(Board $board, Team $team): RedirectResponse
    {
        abort_unless(Auth::user()->canEditBoard($board), 403);

        BoardTeam::where('board_id', $board->id)->where('team_id', $team->id)->delete();

        return back()->with('board-save', 'Team removed from board.');
    }
}
