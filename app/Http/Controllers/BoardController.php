<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    private const BOARD_WITH = ['authors.user', 'boardTeams.team'];

    /**
     * Public board list — only listed boards, matching the old
     * BoardsService::findAll(). Admin sees every board via a separate route
     * (see Admin\BoardController, not yet ported — docs/backlog.md).
     */
    public function index(): Response
    {
        $boards = Board::where('is_listed', true)
            ->with(self::BOARD_WITH)
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Boards/Index', ['boards' => $boards]);
    }

    public function show(Board $board): Response
    {
        $board->load([...self::BOARD_WITH, 'tiles.task']);

        $playerBoard = Auth::check()
            ? $board->playerBoards()
                ->where('user_id', Auth::id())
                ->with('completedTiles:id,player_board_id,tile_id')
                ->first()
            : null;

        return Inertia::render('BoardShow', [
            'board' => $board,
            'tiles' => $board->tiles,
            'playerBoard' => $playerBoard === null ? null : [
                ...$playerBoard->only(['id', 'current_position', 'dice_rolls_today']),
                'completedTileIds' => $playerBoard->completedTiles->pluck('tile_id'),
            ],
            'canEdit' => Auth::check() && $board->authors()->where('user_id', Auth::id())->exists()
                || (Auth::user()?->isAdmin() ?? false),
        ]);
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
}
