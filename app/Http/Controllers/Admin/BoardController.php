<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BoardController as EventController;
use App\Http\Controllers\BoardInviteController;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BoardInvite;
use App\Models\Event;
use App\Models\Team;
use App\Notifications\EventStatusChanged;
use App\Services\BoardAccessService;
use App\Services\EventNotificationService;
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
    /** Statuses the list can be narrowed to — anything else falls back to 'all'. */
    private const STATUSES = ['active', 'paused', 'deleted'];

    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $search = $request->string('search')->toString();
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : 'all';

        // By column — see EventController::EVENT_WITH. The admin list
        // renders the same author names and nothing more.
        // withTrashed, because this page is where a deleted event is put
        // back — see restore(). They are listed dimmed and last rather than
        // in place, so the list still reads as "the events" at a glance.
        $boards = Event::withTrashed()
            ->with(['authors.user:id,discord_username,nickname,avatar_url', 'eventTeams.team', 'board'])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            // Paused/active only make sense among events still standing —
            // 'deleted' is its own branch below, via onlyTrashed().
            ->when($status === 'active', fn ($q) => $q->whereNull('deleted_at')->whereNull('paused_at'))
            ->when($status === 'paused', fn ($q) => $q->whereNull('deleted_at')->whereNotNull('paused_at'))
            ->when($status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->orderByRaw('deleted_at is null desc')
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Admin/Boards', [
            'boards' => $boards,
            // Echoed back rather than read straight off the request client
            // side, so the search box and status select reflect what the
            // server actually filtered on — including the fallback to 'all'
            // for a query string nobody meant to type by hand.
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function update(Request $request, Event $event, EventController $events): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->update($request, $event, asAdmin: true);
    }

    public function pause(
        Request $request,
        Event $event,
        EventController $events,
        EventNotificationService $notifier,
    ): RedirectResponse {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->pause($request, $event, $notifier, asAdmin: true);
    }

    public function destroy(
        Request $request,
        Event $event,
        EventController $events,
        EventNotificationService $notifier,
    ): RedirectResponse {
        abort_unless(Auth::user()->isAdmin(), 403);

        return $events->destroy($request, $event, $notifier, asAdmin: true);
    }

    /**
     * Put a deleted event back.
     *
     * Admin-only and deliberately not offered to the host who deleted it:
     * undo belongs to the person who can see the whole list, and a host who
     * changes their mind an hour later is asking for something more than a
     * button — they are asking somebody to check nothing has been rebuilt in
     * the meantime.
     *
     * `withTrashed()` because the route binds by id and a trashed event is
     * invisible to the default query — the whole point of the soft delete.
     */
    public function restore(string $eventId, EventNotificationService $notifier): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $event = Event::withTrashed()->whereKey($eventId)->firstOrFail();

        $event->restore();

        AuditLog::record('event.restored', $event);

        // Always announced, and this one is not optional. Everybody who
        // joined was told the event was cancelled; leaving them with that as
        // the last word about an event that is running again is the one case
        // where silence actively misinforms.
        $notifier->announce($event, EventStatusChanged::RESTORED, Auth::user());

        return back()->with('board-save', trans('admin.event_restored'));
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
