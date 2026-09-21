<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BoardAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The old leaderboard address. The ranking lives on the participants page
 * now — see ParticipantController and BoardLeaderboardService — and this
 * stays so links already out there keep landing somewhere.
 */
class LeaderboardController extends Controller
{
    public function show(Event $event, BoardAccessService $access): RedirectResponse
    {
        abort_unless($access->canView(Auth::user(), $event), 403);

        // An event with no board has no such ranking; its standings are on
        // the event page.
        if ($event->board === null) {
            return redirect()->route('events.show', $event);
        }

        return redirect()->route('events.participants', $event);
    }
}
