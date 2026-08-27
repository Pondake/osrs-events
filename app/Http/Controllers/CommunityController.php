<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A dashboard for the Community nav group, same shape as the events hub
 * (BoardController::index) — a slice of each thing under it, not a flat
 * list. Asked for after the boards hub made the same complaint true of
 * Community: the nav advertises Teams/Leaderboards/Clans as one group and
 * none of them were discoverable from a shared landing spot.
 *
 * Two of the three are still "Soon" — see nav.leaderboards/nav.clans — so
 * this renders real content for Teams and an explained placeholder for the
 * other two, same convention Boards/Index.vue uses for its own Calendar row.
 */
class CommunityController extends Controller
{
    private const HUB_SLICE = 3;

    public function index(): Response
    {
        $user = Auth::user();

        $query = Team::with('members.user')
            ->visibleTo($user)
            ->whereHas('members', fn ($m) => $m->where('user_id', $user->id))
            ->orderBy('name');

        $teams = (clone $query)->take(self::HUB_SLICE)->get()->map(fn (Team $team) => [
            ...$team->only(['id', 'name', 'icon_url', 'guild_name']),
            'memberCount' => $team->members->count(),
            'viewerRole' => $team->roleFor($user),
        ]);

        return Inertia::render('Community/Index', [
            'teams' => $teams,
            'teamsTotal' => (clone $query)->count(),
        ]);
    }
}
