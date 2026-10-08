<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

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
     * Called: the event is over, and its results are the results.
     *
     * Distinct from CANCELLED, which is the event never happening — a
     * cancelled event has no page left to link to and nothing came of it,
     * where an ended one has a podium and is worth going to look at. Sent
     * both by a host pressing End now and by the first finish on an event
     * whose rule is STOP.
     */
    public const ENDED = 'ended';

    /** A host taking that back — the event runs again. */
    public const REOPENED = 'reopened';

    /**
     * An admin undid a deletion. Nobody was told about this at first, which
     * left everyone holding a "this has been cancelled" email about an event
     * that was running again — the one state where saying nothing is worse
     * than saying something.
     */
    public const RESTORED = 'restored';

    /**
     * The colour of the label at the top of the mail, per change — see the
     * .status-* rules in the mail theme. Three of them are the same good news
     * ("it runs again"), so they share a tone; the label says which.
     */
    private const TONES = [
        self::PAUSED => 'paused',
        self::RESUMED => 'live',
        self::REOPENED => 'live',
        self::RESTORED => 'live',
        self::ENDED => 'finished',
        self::CANCELLED => 'cancelled',
    ];

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
        /** An Event::EVENT_TYPES key, shown above the title ("Bingo"). */
        public readonly ?string $eventType = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = ['event' => $this->eventTitle];

        // Both squished onto one line: they are a host's own typing, and the
        // template sets them in an HTML block that a blank line would end
        // early, spilling the rest into Markdown.
        $title = Str::squish($this->eventTitle);
        $reason = filled($this->reason) ? Str::squish($this->reason) : null;

        $mail = (new MailMessage)
            ->subject(trans("notifications.event_{$this->change}_subject", $event))
            ->greeting(trans('notifications.greeting', ['name' => $notifiable->displayName()]))
            ->line(trans("notifications.event_{$this->change}_line"));

        // A cancelled event has no page left, and a button leading to a 404
        // is worse than no button — so that one mail points at the events
        // that are still on, which is the useful next step anyway.
        $url = $this->url ?? ($this->change === self::CANCELLED ? route('events.index') : null);

        if ($url !== null) {
            $mail->action(trans("notifications.event_{$this->change}_action"), $url);
        }

        return $mail->markdown('mail.notice', [
            'hero' => [
                'tone' => self::TONES[$this->change],
                'label' => trans("notifications.event_{$this->change}_label"),
                'title' => $title,
                'eyebrow' => $this->eventType !== null ? trans('events.type_'.strtolower($this->eventType)) : null,
            ],
            // The host's reason, in their words, set apart. It is the part
            // people actually want — "paused" they can see for themselves —
            // so it is also what the inbox preview shows.
            'quote' => $reason,
            'quoteLabel' => trans('notifications.event_reason_label'),
            'preheader' => $reason ?? trans("notifications.event_{$this->change}_preheader"),
            'reason' => trans('notifications.event_footer', $event),
        ]);
    }
}
