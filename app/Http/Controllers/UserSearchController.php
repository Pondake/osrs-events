<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ported from the old GraphQL `users(search)` query (see stale/frontend's
 * useUsers.ts's fetchUsers()) — used by BoardSettingsModal's co-author
 * search, which needs a generic (not board- or team-scoped) user lookup.
 * TeamController::searchUsers() is the closer precedent for the pattern,
 * but excludes existing team members server-side since it's scoped to one
 * team; this one isn't scoped to anything, so already-selected authors are
 * filtered out client-side instead (same as the old app's fetchUsers()).
 *
 * No extra permission gate beyond the route's own 'auth' middleware — a
 * board *owner* who can open BoardSettingsModal in the first place (via
 * canEditEvent(), not the global canCreateBoards permission) needs to
 * search for co-editors too, and this endpoint has no board context to
 * check canEditEvent() against. A first version gated on
 * canCreateBoards/admin, which 403'd for exactly that case — caught live
 * while testing the actual search box, not by reading the code. The
 * returned fields (username, nickname, avatar) aren't sensitive; any
 * logged-in user reaching this route at all is enough.
 */
class UserSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $users = User::when($search, fn ($q) => $q->where('discord_username', 'like', "%{$search}%"))
            ->orderBy('discord_username')
            ->limit(20)
            ->get(['id', 'discord_username', 'nickname', 'avatar_url']);

        return response()->json($users);
    }
}
