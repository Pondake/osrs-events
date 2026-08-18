<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ported from the old GraphQL `users(search)` query (see stale/frontend's
 * useUsers.ts's fetchUsers()) — used by BoardSettingsModal's co-author
 * search, which needs a generic (not board- or team-scoped) user lookup.
 * TeamController::searchUsers() is the closer precedent for the pattern,
 * but excludes existing team members server-side since it's scoped to one
 * team; this one isn't scoped to anything, so already-selected authors are
 * filtered out client-side instead (same as the old app's fetchUsers()).
 */
class UserSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(Auth::user()->hasPermission('canCreateBoards') || Auth::user()->isAdmin(), 403);

        $search = $request->string('search')->toString();

        $users = User::when($search, fn ($q) => $q->where('discord_username', 'like', "%{$search}%"))
            ->orderBy('discord_username')
            ->limit(20)
            ->get(['id', 'discord_username', 'nickname', 'avatar_url']);

        return response()->json($users);
    }
}
