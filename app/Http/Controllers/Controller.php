<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;

abstract class Controller
{
    /**
     * The one place the "who may change this event" rule is written down.
     *
     * `$asAdmin` is not a permission a caller can grant itself: it is only
     * ever passed by the thin methods in Admin\BoardController, which sit
     * behind the admin route group and assert `isAdmin()` before delegating.
     * Everything reachable from the public side of the app leaves it false,
     * which is what makes an admin an ordinary user out there.
     */
    protected function assertCanEditEvent(?User $user, Event $event, bool $asAdmin = false): void
    {
        abort_if($user === null, 403);
        abort_unless($asAdmin || $user->canEditEvent($event), 403);
    }

    /** The same split, for the actions reserved to the event's owner. */
    protected function assertOwnsEvent(?User $user, Event $event, bool $asAdmin = false): void
    {
        abort_if($user === null, 403);
        abort_unless($asAdmin || $user->isEventOwner($event), 403);
    }
}
