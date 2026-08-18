<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\UserGuild;
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

        $team->update($data);

        return back()->with('board-save', 'Team updated.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorizeManage($team);
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

        return back()->with('board-save', 'Member added.');
    }

    public function removeMember(Team $team, string $userId): RedirectResponse
    {
        $this->authorizeManage($team);

        TeamMember::where(['team_id' => $team->id, 'user_id' => $userId])->delete();

        return back()->with('board-save', 'Member removed.');
    }

    /** Ported from assertManagerOrAdmin() — admin or TEAM_MANAGER only. */
    private function authorizeManage(Team $team): void
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || $user->hasRole('TEAM_MANAGER'), 403);
    }
}
