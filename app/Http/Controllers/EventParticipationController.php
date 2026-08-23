<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Joining and leaving, for every event type.
 *
 * One pair of routes rather than a different verb per type: the button says
 * the same thing on a bingo card as on a race, and what that means for the
 * type is the service's business.
 */
class EventParticipationController extends Controller
{
    public function store(Request $request, Event $event, EventParticipationService $participation): RedirectResponse
    {
        $data = $request->validate(['token_or_code' => ['nullable', 'string']]);

        try {
            $needsTeam = $participation->join($request->user(), $event, $data['token_or_code'] ?? null);
        } catch (ValidationException $e) {
            // An access failure belongs under the invite field the gate page
            // has; everything else is a toast, because the page it happens on
            // has no field to hang it under.
            if (array_key_exists('access', $e->errors())) {
                return back()->withErrors($e->errors());
            }

            return back()->with('board-save-error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('events.show', $event)
            ->with('board-save', trans($needsTeam ? 'events.joined_needs_team' : 'events.joined'));
    }

    public function destroy(Request $request, Event $event, EventParticipationService $participation): RedirectResponse
    {
        try {
            $participation->leave($request->user(), $event);
        } catch (ValidationException $e) {
            return back()->with('board-save-error', collect($e->errors())->flatten()->first());
        }

        return back()->with('board-save', trans('events.left'));
    }
}
