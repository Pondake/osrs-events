<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\BoardInvite;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page for the admin area.
 *
 * Every number here is counted live rather than kept in a stats table — at
 * this scale that's a handful of indexed COUNTs, and a cached figure that
 * silently goes stale is worse than one that costs a query.
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'boards' => Event::count(),
                'teams' => Team::count(),
                'tasks' => Task::count(),
                // Only the invites that still work — the total would count
                // expired and exhausted links, which is not what "invites"
                // means when you're glancing at a dashboard.
                'liveInvites' => BoardInvite::query()
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('use_count', '<', 'max_uses'))
                    ->count(),
            ],
            // The dashboard's real job: what changed recently, and who did
            // it. Deliberately the audit log rather than a separate feed —
            // one source of truth for "what happened".
            'recentActivity' => AuditLog::query()
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(['id', 'action', 'actor_label', 'target_label', 'team_label', 'created_at']),
            'newestUsers' => User::query()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'discord_username', 'nickname', 'email', 'avatar_url', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->displayName(),
                    'avatarUrl' => $user->avatar_url,
                    'createdAt' => $user->created_at,
                ]),
        ]);
    }
}
