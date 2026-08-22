<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BoardController as EventController;
use App\Http\Controllers\BoardInviteController;
use App\Http\Controllers\Controller;
use App\Models\BoardInvite;
use App\Models\Event;
use App\Models\Team;
use App\Services\BoardAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every event, and the power to change any of them.
 *
 * This is the only place a site admin can edit an event they did not author.
 * On the public side of the app `User::canEditEvent()` answers on authorship
 * alone, so an admin browsing the site is an ordinary user there — no extra
 * buttons, no quiet writes. Reaching for the power means coming here.
 *
 * The work itself is not duplicated: each method asserts admin and then hands
 * off to the same controller the public routes use, passing `asAdmin: true`.
 * One implementation, one set of validation rules, one audit trail — the only
 * thing that differs is who is allowed to arrive.
 */
class BoardController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        // By column — see EventController::EVENT_WITH. The admin list
        // renders the same author names and nothing more.
        $boards = Event::with(['authors.user:id,discord_username,nickname,avatar_url', 'eventTeams.team', 'board'])
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Admin/Boards', ['boards' => $boards]);
    }

    public function update(Request $request, Event $event, EventController $events): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->update($request, $event, asAdmin: true);
    }

    public function destroy(Event $event, EventController $events): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->destroy($event, asAdmin: true);
    }

    // ------------------------------------------------------------ the teams

    public function teamsIndex(Request $request, Event $event, EventController $events): JsonResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->teamsIndex($request, $event, asAdmin: true);
    }

    public function addTeam(Request $request, Event $event, EventController $events): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->addTeam($request, $event, asAdmin: true);
    }

    public function removeTeam(Event $event, Team $team, EventController $events): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->removeTeam($event, $team, asAdmin: true);
    }

    // ---------------------------------------------------------- the invites

    public function invitesIndex(Event $event, BoardInviteController $invites): JsonResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $invites->index($event, asAdmin: true);
    }

    public function storeInvite(
        Request $request,
        Event $event,
        BoardAccessService $access,
        BoardInviteController $invites,
    ): JsonResponse {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $invites->store($request, $event, $access, asAdmin: true);
    }

    public function destroyInvite(Event $event, BoardInvite $invite, BoardInviteController $invites): JsonResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $invites->destroy($event, $invite, asAdmin: true);
    }
}
