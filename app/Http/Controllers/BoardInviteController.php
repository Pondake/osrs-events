<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Board;
use App\Models\BoardInvite;
use App\Models\Event;
use App\Services\BoardAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Invite links for an event, for anyone who hosts it.
 *
 * These three are consumed by fetch() from the board settings modal, not by
 * Inertia, so they answer in JSON. They used to return back()->with(flash):
 * fetch follows the 302 into a full page render, the flash is spent on that
 * discarded response, and it surfaced later as a stray or empty toast
 * attached to whatever the user did next. Creating two invites and deleting
 * one was enough to see it.
 */
class BoardInviteController extends Controller
{
    /**
     * How many usable links one event may have at once.
     *
     * An invite link is a credential, and every extra open one is another
     * thing that has to be tracked and revoked. Three is enough for the real
     * cases — a Discord post, a forum thread, a DM — and small enough that
     * the list stays readable. Expired and used-up links do not count, so
     * the limit is always clearable.
     */
    private const MAX_OPEN = 3;

    /**
     * Every action here is a HOST action, not an owner one.
     *
     * It used to be owner-only, while the tab it lives in renders for anyone
     * who may edit the event — so a co-host opened Invite links and every
     * button in it answered 403, which reaches them as "Something went
     * wrong. Please try again." Reported exactly that way.
     *
     * Handing out and revoking links is day-to-day running of an event, the
     * same as adding a team or pausing it. Deleting the event stays the
     * owner's alone, because that is the one that destroys other people's
     * work.
     */
    public function index(Event $event, bool $asAdmin = false): JsonResponse
    {
        $this->assertCanEditEvent(Auth::user(), $event, $asAdmin);

        return $this->list($event);
    }

    /** One shape for every response, so the client never has to branch. */
    private function list(Event $event): JsonResponse
    {
        $invites = $event->invites()->orderByDesc('created_at')->get();

        return response()->json([
            'invites' => $invites,
            'openCount' => $event->invites()->open()->count(),
            'maxOpen' => self::MAX_OPEN,
        ]);
    }

    public function store(Request $request, Event $event, BoardAccessService $access, bool $asAdmin = false): JsonResponse
    {
        $this->assertCanEditEvent(Auth::user(), $event, $asAdmin);

        // Checked before validation so the message is about the limit rather
        // than about a field the caller never sent.
        if ($event->invites()->open()->count() >= self::MAX_OPEN) {
            return response()->json([
                'message' => trans('admin.invite_limit_reached', ['max' => self::MAX_OPEN]),
            ], 422);
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ]);

        $invite = BoardInvite::create([
            'id' => (string) str()->uuid(),
            'event_id' => $event->id,
            'token' => (string) str()->uuid(),
            'short_code' => $access->generateUniqueShortCode($event),
            'created_by' => Auth::id(),
            ...$data,
        ]);

        // Logged here as well as in the admin overview, so the trail doesn't
        // depend on which of the two surfaces the action was taken from.
        // The token is deliberately never logged — it IS the credential.
        AuditLog::record('invite.created', $invite, [
            'board' => $event->title,
            'short_code' => $invite->short_code,
            'max_uses' => $invite->max_uses,
        ]);

        return $this->list($event);
    }

    public function destroy(Event $event, BoardInvite $invite, bool $asAdmin = false): JsonResponse
    {
        $this->assertCanEditEvent(Auth::user(), $event, $asAdmin);
        abort_unless($invite->event_id === $event->id, 404);

        AuditLog::record('invite.revoked', $invite, [
            'board' => $event->title,
            'short_code' => $invite->short_code,
            'use_count' => $invite->use_count,
        ]);

        $invite->delete();

        return $this->list($event);
    }
}
