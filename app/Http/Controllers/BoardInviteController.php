<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Board;
use App\Models\BoardInvite;
use App\Services\BoardAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Ported from InvitesService — every action requires board-owner-or-admin. */
class BoardInviteController extends Controller
{
    public function index(Board $board): JsonResponse
    {
        abort_unless(Auth::user()->isBoardOwnerOrAdmin($board), 403);

        return response()->json($board->invites()->orderByDesc('created_at')->get());
    }

    public function store(Request $request, Board $board, BoardAccessService $access): RedirectResponse
    {
        abort_unless(Auth::user()->isBoardOwnerOrAdmin($board), 403);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ]);

        $invite = BoardInvite::create([
            'id' => (string) str()->uuid(),
            'board_id' => $board->id,
            'token' => (string) str()->uuid(),
            'short_code' => $access->generateUniqueShortCode($board),
            'created_by' => Auth::id(),
            ...$data,
        ]);

        // Logged here as well as in the admin overview, so the trail doesn't
        // depend on which of the two surfaces the action was taken from.
        // The token is deliberately never logged — it IS the credential.
        AuditLog::record('invite.created', $invite, [
            'board' => $board->title,
            'short_code' => $invite->short_code,
            'max_uses' => $invite->max_uses,
        ]);

        return back()->with('board-save', 'Invite created.');
    }

    public function destroy(Board $board, BoardInvite $invite): RedirectResponse
    {
        abort_unless(Auth::user()->isBoardOwnerOrAdmin($board), 403);
        abort_unless($invite->board_id === $board->id, 404);

        AuditLog::record('invite.revoked', $invite, [
            'board' => $board->title,
            'short_code' => $invite->short_code,
            'use_count' => $invite->use_count,
        ]);

        $invite->delete();

        return back()->with('board-save', 'Invite revoked.');
    }
}
