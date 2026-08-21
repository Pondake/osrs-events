<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BoardAccessService;
use App\Services\EventStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Entering and leaving a metric race.
 *
 * The live channel moved to EventStreamController when every event type got
 * one — this is now only the two actions that change who is competing.
 */
class SkillRaceController extends Controller
{
    /**
     * Enter the current user into the race.
     *
     * Separate from joining the event: access says who may look, this says
     * who is competing. An OPEN event grants access without recording it, so
     * there is nothing to derive participation from even if we wanted to.
     */
    public function enter(
        Request $request,
        Event $event,
        BoardAccessService $access,
        EventStandingsService $standings,
    ): RedirectResponse {
        // Both metric types, not just skill races — a drop race is entered
        // exactly the same way.
        abort_unless($event->needsMetric(), 404);
        abort_unless($access->hasAccess($request->user(), $event), 403);

        try {
            if ($standings->enter($event, $request->user()) === null) {
                // Not a validation error on a field this form doesn't have —
                // the missing piece is over on the profile page, and the
                // message says so.
                return back()->with('board-save-error', trans('events.enter_needs_rsn'));
            }
        } catch (ValidationException $e) {
            // Same treatment for the same reason: a toast, because there is no
            // field on this page to hang an error message under.
            return back()->with('board-save-error', $e->errors()['osrs_username'][0]);
        }

        return back()->with('board-save', trans('events.entered'));
    }

    public function leave(
        Request $request,
        Event $event,
        EventStandingsService $standings,
    ): RedirectResponse {
        abort_unless($event->needsMetric(), 404);

        $standings->leave($event, $request->user());

        return back()->with('board-save', trans('events.left'));
    }
}
