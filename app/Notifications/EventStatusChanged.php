<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "The event you joined has been paused / resumed / cancelled."
 *
 * One class for the three, because they are one message with three verbs —
 * same audience, same envelope, same single call to action. Three
 * notification classes would be three copies of that envelope to keep in
 * step.
 *
 * **Plain scalars, not an Event model.** Two reasons, and the second one is
 * the load-bearing one:
 *
 *  - It is queued, so a model property would be serialized by id and
 *    re-fetched when the job runs. For the cancellation mail the event is
 *    soft-deleted by then, so the default query finds nothing and the job
 *    dies — the one mail people most need would be the one that never
 *    arrives.
 *  - The mail says what the event *was called at the time*. A title edited
 *    between queueing and sending should not rewrite an announcement that
 *    was already made.
 */
class EventStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public const PAUSED = 'paused';

    public const RESUMED = 'resumed';

    public const CANCELLED = 'cancelled';

    /**
     * An admin undid a deletion. Nobody was told about this at first, which
     * left everyone holding a "this has been cancelled" email about an event
     * that was running again — the one state where saying nothing is worse
     * than saying something.
     */
    public const RESTORED = 'restored';

    /**
     * @param  string  $change  one of the constants above
     * @param  string|null  $url  where to go and look — null for a cancelled
     *                            event, which no longer has a page
     */
    public function __construct(
        public readonly string $change,
        public readonly string $eventTitle,
        public readonly ?string $url = null,
        /** The host's own words, when they gave any. Paused only. */
        public readonly ?string $reason = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(trans("notifications.event_{$this->change}_subject", ['event' => $this->eventTitle]))
            ->greeting(trans('notifications.greeting', ['name' => $notifiable->displayName()]))
            ->line(trans("notifications.event_{$this->change}_line", ['event' => $this->eventTitle]));

        // The host's reason, in their words, on its own line. It is the part
        // people actually want — "paused" they can see for themselves.
        if (filled($this->reason)) {
            $mail->line(trans('notifications.event_reason', ['reason' => $this->reason]));
        }

        // A cancelled event has nowhere to send anyone: the page is gone, and
        // a button leading to a 404 is worse than no button.
        if ($this->url !== null) {
            $mail->action(trans('notifications.event_action'), $this->url);
        }

        return $mail->line(trans('notifications.event_footer'));
    }
}
