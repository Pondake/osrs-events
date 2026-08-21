<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BoardAccessService;
use App\Services\EventStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            $standing = $standings->enter($event, $request->user());

            if ($standing === null) {
                // Not a validation error on a field this form doesn't have —
                // the missing piece is over on the profile page, and the
                // message says so.
                return back()->with('board-save-error', trans('events.enter_needs_rsn'));
            }

            // Baseline this row now instead of leaving it blank until the
            // scheduled sync gets to it. Entering and then seeing nothing but
            // "waiting for first sync" for up to ten minutes reads as broken,
            // and it is the first thing anyone does on a race.
            //
            // Synchronous rather than queued: this app runs no queue worker,
            // so a dispatched job would sit in the table forever. It costs one
            // outbound Wise Old Man call on a route now throttled to 10/min
            // per user, which is what makes doing it inline defensible.
            //
            // Failure is deliberately not fatal — refresh() records the
            // problem on the row itself (sync_error), the standings page
            // renders that, and the scheduled sync retries. Losing the entry
            // because a third-party API blinked would be the worse outcome.
            try {
                $standings->refresh($event, $standing);
            } catch (Throwable $e) {
                report($e);
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
