<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only view of the audit log. There is deliberately no store, update or
 * destroy action here and no route for one: a log an admin can edit or clear
 * proves nothing about the admins it exists to hold accountable.
 *
 * Retention, if it's ever needed, belongs in a scheduled prune command with
 * its own explicit window — not in a "clear log" button.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $logs = AuditLog::query()
            ->when(
                $request->string('action')->isNotEmpty(),
                fn ($q) => $q->where('action', $request->string('action')),
            )
            // Exact match on the stored labels, and on BOTH sides: "show me
            // everything about this person" means what they did as well as
            // what was done to them, and an audit page that only answered one
            // of those would quietly hide the half that matters after an
            // account is gone.
            ->when(
                $request->string('user')->isNotEmpty(),
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $user = $request->string('user')->toString();
                    $sub->where('actor_label', $user)->orWhere('target_label', $user);
                }),
            )
            // One control for both dimensions, disambiguated by a prefix:
            // picking a team narrows to that team, picking a clan spans every
            // team in it, since guild_id is stored on team-scoped rows too.
            ->when($request->string('scope')->isNotEmpty(), function ($q) use ($request) {
                [$type, $id] = array_pad(explode(':', $request->string('scope')->toString(), 2), 2, null);

                if ($type === 'team') return $q->where('team_id', $id);
                if ($type === 'guild') return $q->where('guild_id', $id);

                return $q;
            })
            // Matches the stored labels, not a join on users: the rows worth
            // searching for hardest are the ones whose actor or target no
            // longer exists, and a join would drop exactly those.
            ->when(
                $request->string('search')->isNotEmpty(),
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $term = '%'.$request->string('search').'%';
                    $sub->where('actor_label', 'like', $term)
                        ->orWhere('target_label', 'like', $term)
                        ->orWhere('team_label', 'like', $term)
                        ->orWhere('guild_label', 'like', $term);
                }),
            )
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Settings/Admin/Audit', [
            'logs' => $logs,
            'actions' => AuditLog::ACTIONS,
            'users' => $this->userOptions(),
            'scopes' => $this->scopeOptions(),
            'filters' => [
                'action' => $request->string('action')->toString(),
                'user' => $request->string('user')->toString(),
                'scope' => $request->string('scope')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    /**
     * Options come from the log itself, not from the users table.
     *
     * Deliberate: the people most worth filtering for are the ones who have
     * since been deleted, and they aren't in `users` any more. Labels rather
     * than ids for the same reason — a deleted user's id points nowhere.
     *
     * @return array<int, string>
     */
    private function userOptions(): array
    {
        $actors = AuditLog::query()->distinct()->pluck('actor_label');
        $targets = AuditLog::query()->where('target_type', 'User')->distinct()->pluck('target_label');

        return $actors->merge($targets)
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Teams and guilds present in the log, as prefixed values the index()
     * filter above splits back apart.
     *
     * @return array<int, array{value: string, label: string, type: string}>
     */
    private function scopeOptions(): array
    {
        $teams = AuditLog::query()
            ->whereNotNull('team_id')
            ->get(['team_id', 'team_label'])
            ->unique('team_id')
            ->map(fn ($log) => [
                'value' => 'team:'.$log->team_id,
                'label' => $log->team_label,
                'type' => 'team',
            ]);

        $guilds = AuditLog::query()
            ->whereNotNull('guild_id')
            ->get(['guild_id', 'guild_label'])
            ->unique('guild_id')
            ->map(fn ($log) => [
                'value' => 'guild:'.$log->guild_id,
                'label' => $log->guild_label ?: $log->guild_id,
                'type' => 'guild',
            ]);

        // Each group sorted internally, then clans ahead of teams — the wider
        // scope reads as the outer grouping. Sorting the concatenation as a
        // whole would interleave them back together.
        return $guilds->sortBy('label')
            ->concat($teams->sortBy('label'))
            ->values()
            ->all();
    }
}
