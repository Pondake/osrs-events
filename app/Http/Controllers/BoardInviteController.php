<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardInvite;
use App\Services\BoardAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Ported from InvitesService — every action requires board-owner-or-admin. */
class BoardInviteController extends Controller
{
    public function store(Request $request, Board $board, BoardAccessService $access): RedirectResponse
    {
        abort_unless(Auth::user()->isBoardOwnerOrAdmin($board), 403);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ]);

        BoardInvite::create([
            'id' => (string) str()->uuid(),
            'board_id' => $board->id,
            'token' => (string) str()->uuid(),
            'short_code' => $access->generateUniqueShortCode($board),
            'created_by' => Auth::id(),
            ...$data,
        ]);

        return back()->with('board-save', 'Invite created.');
    }

    public function destroy(Board $board, BoardInvite $invite): RedirectResponse
    {
        abort_unless(Auth::user()->isBoardOwnerOrAdmin($board), 403);
        abort_unless($invite->board_id === $board->id, 404);

        $invite->delete();

        return back()->with('board-save', 'Invite revoked.');
    }
}
