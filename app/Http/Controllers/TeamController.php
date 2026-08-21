<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ported from the old TeamsService, with its visibility rule replaced —
 * see Team::scopeVisibleTo(). The original said "your guilds, plus any team
 * with no guild", which made every private team on the site public to every
 * account, because a guild has always been optional.
 *
 * What is NOT ported is the permission rule. The old assertManagerOrAdmin()
 * asked one global question — "are you an admin, or do you hold the
 * TEAM_MANAGER role?" — which meant the person who created a team could not
 * rename it, add anyone to it, or delete it, while a single global role
 * granted all of that over every team on the site. Rights now live on the
 * membership row (TeamMember::$role); see Team::isManagedBy()/isOwnedBy().
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $query = Team::with(['members.user'])->visibleTo($user)->orderBy('name');

        // The page used to gate its buttons on isAdmin alone, with a comment
        // conceding that a real per-team check "isn't available client-side
        // without shipping every user's role set down". It is available: the
        // answer is three booleans per team, decided here by the same
        // methods the write endpoints re-check with.
        $teams = $query->get()->map(fn (Team $team) => [
            ...$team->only(['id', 'name', 'icon_url', 'guild_id', 'guild_name']),
            'members' => $team->members->map(fn (TeamMember $member) => [
                'id' => $member->id,
                'role' => $member->role,
                'user' => $member->user?->only(['id', 'nickname', 'discord_username', 'avatar_url']),
            ])->values(),
            'viewerRole' => $team->roleFor($user),
            'canManage' => $team->isManagedBy($user),
            'canDelete' => $team->isOwnedBy($user),
        ]);

        return Inertia::render('Teams/Index', ['teams' => $teams]);
    }

    /**
     * The teams this user could put on an event, as plain JSON.
     *
     * Exists so BoardSettingsModal's Teams tab works while the event is
     * still being created — before this, the tab could only ever say "save
     * the board first", because the only team list on offer hung off an
     * event id that did not exist yet.
     */
    public function options(Request $request): JsonResponse
    {
        $teams = Team::query()
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get(['id', 'name', 'guild_name']);

        return response()->json(['teams' => $teams]);
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

        // Auto-add the creator as the first member, same as the old service —
        // as OWNER, which is the whole point: creating a team now grants the
        // right to manage it.
        TeamMember::create([
            'id' => (string) str()->uuid(),
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'role' => TeamMember::OWNER,
        ]);

        AuditLog::record('team.created', $team, [], $team);

        return back()->with('board-save', trans('teams.created'));
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

        return back()->with('board-save', trans('teams.updated'));
    }

    public function destroy(Team $team): RedirectResponse
    {
        // Owner (or admin) only, unlike everything else here — a promoted
        // manager can rename the team and move its members around, but
        // deleting takes the team's whole history with it.
        abort_unless($team->isOwnedBy(Auth::user()), 403);

        // Before the delete — the team is its own scope here, so both the
        // target label and the team/guild labels have to be read while it
        // still exists.
        AuditLog::record('team.deleted', $team, [
            'members' => $team->members()->count(),
        ], $team);

        $team->delete();

        return back()->with('board-save', trans('teams.deleted'));
    }

    public function addMember(Request $request, Team $team): RedirectResponse
    {
        $this->authorizeManage($team);

        $data = $request->validate(['user_id' => ['required', 'uuid', 'exists:users,id']]);

        TeamMember::firstOrCreate(['team_id' => $team->id, 'user_id' => $data['user_id']], [
            'id' => (string) str()->uuid(),
            'role' => TeamMember::MEMBER,
        ]);

        // Target is the member, scope is the team — the two dimensions the
        // audit page filters on independently.
        AuditLog::record('team.member_added', User::find($data['user_id']), [], $team);

        return back()->with('board-save', trans('teams.member_added'));
    }

    /**
     * Promote a member to MANAGER, or demote them back to MEMBER.
     *
     * Owner-only rather than manager-only, so a promoted manager cannot
     * quietly promote more of them, and OWNER is not a value this accepts:
     * a team has exactly one, and handing it over is a different action
     * from handing out management.
     */
    public function updateMemberRole(Request $request, Team $team, string $userId): RedirectResponse
    {
        abort_unless($team->isOwnedBy(Auth::user()), 403);

        $data = $request->validate([
            'role' => ['required', Rule::in([TeamMember::MANAGER, TeamMember::MEMBER])],
        ]);

        $member = TeamMember::where(['team_id' => $team->id, 'user_id' => $userId])->firstOrFail();

        // Demoting the owner would leave the team with nobody who can delete
        // it, and no route back — there is no "make me owner" action.
        abort_if($member->role === TeamMember::OWNER, 403, 'The team owner cannot be demoted.');

        // Read before the write: save() re-syncs the original attributes, so
        // asking the model afterwards returns the value just written.
        $previousRole = $member->role;

        $member->update(['role' => $data['role']]);

        AuditLog::record('team.member_role_changed', User::find($userId), [
            'role' => ['from' => $previousRole, 'to' => $data['role']],
        ], $team);

        return back()->with('board-save', trans(
            $data['role'] === TeamMember::MANAGER ? 'teams.member_promoted' : 'teams.member_demoted',
        ));
    }

    public function removeMember(Team $team, string $userId): RedirectResponse
    {
        $this->authorizeManage($team);

        // Same reasoning as the demote guard above: a team without its owner
        // is a team nobody can delete.
        $member = TeamMember::where(['team_id' => $team->id, 'user_id' => $userId])->first();
        abort_if($member?->role === TeamMember::OWNER, 403, 'The team owner cannot be removed.');

        $member?->delete();

        AuditLog::record('team.member_removed', User::find($userId), [], $team);

        return back()->with('board-save', trans('teams.member_removed'));
    }

    /** Rename the team, and add/remove its members — owner or manager. */
    private function authorizeManage(Team $team): void
    {
        abort_unless($team->isManagedBy(Auth::user()), 403);
    }

    /** Lightweight user search for the members modal's add-member autocomplete. */
    public function searchUsers(Request $request, Team $team): JsonResponse
    {
        $this->authorizeManage($team);

        $search = $request->string('search')->toString();
        $existingMemberIds = $team->members()->pluck('user_id');

        // Searches nickname and email too — an email-registered account has
        // no discord_username and would otherwise never appear here.
        $users = User::when($search, fn ($q) => $q->where(function ($sub) use ($search) {
            $sub->where('discord_username', 'like', "%{$search}%")
                ->orWhere('nickname', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }))
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('discord_username')
            ->limit(20)
            ->get(['id', 'discord_username', 'nickname', 'avatar_url']);

        return response()->json($users);
    }
}
