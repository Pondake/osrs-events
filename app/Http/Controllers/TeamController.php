<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserGuild;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ported from the old TeamsService. Visibility rule preserved exactly:
 * admins see every team; everyone else sees teams whose guildId matches one
 * of their synced Discord guilds (UserGuild), plus any unguilded team.
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $query = Team::with(['members.user'])->orderBy('name');

        if (! $user?->isAdmin()) {
            $guildIds = UserGuild::where('user_id', Auth::id())->pluck('guild_id');
            $query->where(fn ($q) => $q->whereNull('guild_id')->orWhereIn('guild_id', $guildIds));
        }

        return Inertia::render('Teams/Index', ['teams' => $query->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon_url' => ['nullable', 'string'],
            'guild_id' => ['nullable', 'string'],
            'guild_name' => ['nullable', 'string'],
        ]);

        $team = Team::create(['id' => (string) str()->uuid(), ...$data]);

        // Auto-add the creator as the first member, same as the old service.
        TeamMember::create([
            'id' => (string) str()->uuid(),
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
        ]);

        AuditLog::record('team.created', $team, [], $team);

        return back()->with('board-save', 'Team created.');
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeManage($team);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'icon_url' => ['nullable', 'string'],
            'guild_id' => ['nullable', 'string'],
            'guild_name' => ['nullable', 'string'],
        ]);

        // Diffed before the update so the entry says what changed, not what
        // the form happened to submit — same reasoning as SiteSettingsController.
        $changes = [];
        foreach ($data as $key => $value) {
            if ($team->{$key} !== $value) {
                $changes[$key] = ['from' => $team->{$key}, 'to' => $value];
            }
        }

        $team->update($data);

        if ($changes !== []) {
            AuditLog::record('team.updated', $team, $changes, $team);
        }

        return back()->with('board-save', 'Team updated.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorizeManage($team);

        // Before the delete — the team is its own scope here, so both the
        // target label and the team/guild labels have to be read while it
        // still exists.
        AuditLog::record('team.deleted', $team, [
            'members' => $team->members()->count(),
        ], $team);

        $team->delete();

        return back()->with('board-save', 'Team deleted.');
    }

    public function addMember(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeManage($team);

        $data = $request->validate(['user_id' => ['required', 'uuid', 'exists:users,id']]);

        TeamMember::firstOrCreate(['team_id' => $team->id, 'user_id' => $data['user_id']], [
            'id' => (string) str()->uuid(),
        ]);

        // Target is the member, scope is the team — the two dimensions the
        // audit page filters on independently.
        AuditLog::record('team.member_added', User::find($data['user_id']), [], $team);

        return back()->with('board-save', 'Member added.');
    }

    public function removeMember(Team $team, string $userId): RedirectResponse
    {
        $this->authorizeManage($team);

        TeamMember::where(['team_id' => $team->id, 'user_id' => $userId])->delete();

        AuditLog::record('team.member_removed', User::find($userId), [], $team);

        return back()->with('board-save', 'Member removed.');
    }

    /** Ported from assertManagerOrAdmin() — admin or TEAM_MANAGER only. */
    private function authorizeManage(Team $team): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->hasRole('TEAM_MANAGER'), 403);
    }

    /** Lightweight user search for the members modal's add-member autocomplete. */
    public function searchUsers(Request $request, Team $team): JsonResponse
    {
        $search = $request->string('search')->toString();
        $existingMemberIds = $team->members()->pluck('user_id');

        $users = User::when($search, fn ($q) => $q->where('discord_username', 'like', "%{$search}%"))
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('discord_username')
            ->limit(20)
            ->get(['id', 'discord_username', 'nickname', 'avatar_url']);

        return response()->json($users);
    }
}
