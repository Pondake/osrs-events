<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Who gets told what — the gate every trigger goes through.
 *
 * WebPushService knows how to encrypt and post. This knows the three rules
 * that decide whether it should:
 *
 *  1. **Did they ask for it.** Per category, defaults from the catalogue.
 *  2. **Have they said it recently.** A per-entity floor, not a global rate:
 *     two events may each notify within the minute, but one event cannot
 *     notify twice about the same thing inside its window.
 *  3. **Is there anywhere to send it.** Expired subscriptions are skipped
 *     without a round trip.
 *
 * The throttle is deliberately **only on the push half**. The SSE channels
 * push every claim, every roll and every rank change to whoever is looking,
 * unthrottled, because a page that is already open costs nothing to update.
 * It is the phone in somebody's pocket that needs restraint.
 */
class PushNotifier
{
    public function __construct(private readonly WebPushService $push) {}

    /**
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function toUser(User $user, PushMessage $message): array
    {
        return $this->toUsers(collect([$user]), $message);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function toUsers(Collection $users, PushMessage $message): array
    {
        $result = WebPushService::emptyResult();

        $wanted = $users
            ->filter()
            ->unique('id')
            ->filter(fn (User $user) => $user->wantsNotification($message->category))
            ->filter(fn (User $user) => $this->passesThrottle($user, $message));

        if ($wanted->isEmpty()) {
            return $result;
        }

        $subscriptions = \App\Models\PushSubscription::query()
            ->active()
            ->whereIn('user_id', $wanted->pluck('id'))
            ->get();

        if ($subscriptions->isEmpty()) {
            // Nothing registered — a real and common state (someone who
            // never installed the app, or only ever opens it on a desktop
            // with notifications denied). Not a failure, and not worth a log
            // line per event.
            return $result;
        }

        $result = $this->push->send($subscriptions, $message);

        // Marked only after the send was actually attempted. Marking first
        // would silence the next hour on the strength of a push that turned
        // out to be un-sendable — the throttle is there to protect people
        // from noise, not to swallow the one message that got through.
        if ($result['sent'] > 0 || $result['failed'] > 0) {
            $wanted->each(fn (User $user) => $this->markThrottled($user, $message));
        }

        return $result;
    }

    /**
     * Everyone who said they are playing this event.
     *
     * EventParticipant, and nothing wider. A public event is readable by the
     * whole internet, and "you looked at this once" is not a reason to buzz
     * somebody's phone — the same rule EventNotificationService applies to
     * email, for the same reason.
     *
     * @param  User|null  $except  usually whoever pressed the button; telling
     *                             them about their own click reads as a bug
     * @return array{sent: int, expired: int, failed: int, skipped: int}
     */
    public function toParticipants(Event $event, PushMessage $message, ?User $except = null): array
    {
        $users = $event->participants()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->reject(fn (User $user) => $except !== null && $user->id === $except->id);

        return $this->toUsers($users, $message);
    }

    /**
     * The throttle key is the message's **tag**, which is also what collapses
     * notifications on the lock screen. One concept, used twice: a sender that
     * has thought about which notifications should replace each other has
     * already answered which ones should be rate-limited together.
     */
    private function throttleKey(User $user, PushMessage $message): string
    {
        return 'push-throttle:'.$user->id.':'.($message->tag ?? $message->category);
    }

    private function passesThrottle(User $user, PushMessage $message): bool
    {
        $window = NotificationCategory::throttleFor($message->category);

        return $window === 0 || ! Cache::has($this->throttleKey($user, $message));
    }

    private function markThrottled(User $user, PushMessage $message): void
    {
        $window = NotificationCategory::throttleFor($message->category);

        if ($window > 0) {
            Cache::put($this->throttleKey($user, $message), true, $window);
        }
    }
}
