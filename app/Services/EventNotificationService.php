<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Notifications\EventStatusChanged;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Support\Facades\Notification;

/**
 * Telling the people who joined that something happened to their event.
 *
 * The audience is EventParticipant — the record of somebody explicitly
 * saying they are playing — and nothing else. Not everyone with access: a
 * public event is readable by the whole internet, and "you looked at this
 * once" is not a reason to land in somebody's inbox.
 *
 * **Most accounts here have no email address at all.** Discord login asks for
 * `identify` and `guilds` and deliberately not `email` (see
 * DiscordController), and `users.email` is nullable, so a Discord-only
 * account has nothing to send to. Those are skipped rather than failed on,
 * and the caller is told how many — a host who announces a cancellation to
 * thirty players should know it reached fourteen of them, not assume it
 * reached all thirty. The onboarding wizard now names event notifications as
 * a reason to add an address, which is the only honest fix for the other
 * sixteen.
 */
class EventNotificationService
{
    public function __construct(
        private readonly DiscordAnnouncer $discord,
        private readonly PushNotifier $push,
    ) {}

    /**
     * @param  string  $change  an EventStatusChanged constant
     * @param  User|null  $except  usually the host who pressed the button —
     *                             they know already, and mailing yourself
     *                             about your own click reads as a bug
     * @return array{sent: int, total: int, discord: bool, pushed: int}
     */
    public function announce(Event $event, string $change, ?User $except = null): array
    {
        $participants = $event->participants()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->reject(fn (User $user) => $except !== null && $user->id === $except->id);

        $reachable = $participants->filter(fn (User $user) => filled($user->email));

        if ($reachable->isNotEmpty()) {
            Notification::send($reachable, new EventStatusChanged(
                $change,
                $event->title,
                // A cancelled event's page is gone the moment this is sent.
                $change === EventStatusChanged::CANCELLED ? null : route('events.show', $event),
                $change === EventStatusChanged::PAUSED ? $event->pause_reason : null,
            ));
        }

        // The channel the event actually lives in, for everybody the mail
        // cannot reach. Same message, said once, no pings — see
        // DiscordAnnouncer. It is not gated on `$except`: a webhook posts to
        // a room, not to a person, so there is nobody to leave out.
        $posted = $this->discord->announce($event, $this->discordMessage($event, $change));

        // The third channel, and the one that reaches the people the other
        // two cannot. Email misses every Discord-only account (no address to
        // send to); Discord reaches the room rather than the person, so a
        // cancellation posted into a busy channel is scrolled past. A push is
        // the only one addressed to an individual who is not currently
        // looking at anything.
        $pushed = $this->push->toParticipants(
            $event,
            new PushMessage(
                title: trans("notifications.push_event_{$change}_title", ['event' => $event->title]),
                body: $change === EventStatusChanged::PAUSED && filled($event->pause_reason)
                    ? $event->pause_reason
                    : trans("notifications.push_event_{$change}_body"),
                // A cancelled event's page is gone by the time this lands, so
                // the tap goes to the events list instead of a 404 — the same
                // reasoning as the mail's missing action button.
                path: $change === EventStatusChanged::CANCELLED ? '/events' : "/events/{$event->id}",
                category: NotificationCategory::EVENT_STATUS,
                // Per event: four status changes to one event collapse into
                // one line, but two different events do not hide each other.
                tag: 'status:'.$event->id,
            ),
            $except,
        );

        return [
            'sent' => $reachable->count(),
            'total' => $participants->count(),
            'discord' => $posted,
            'pushed' => $pushed['sent'],
        ];
    }

    /**
     * The Discord version of the same sentence.
     *
     * Not the email body: an inbox gets a greeting and a button, a channel
     * gets one line somebody can read while scrolling past. The reason, when
     * the host gave one, is the part worth carrying over — it is why they
     * are looking at the message at all.
     */
    private function discordMessage(Event $event, string $change): string
    {
        $line = trans("notifications.discord_event_{$change}", ['event' => $event->title]);

        if ($change === EventStatusChanged::PAUSED && filled($event->pause_reason)) {
            $line .= ' '.$event->pause_reason;
        }

        return $change === EventStatusChanged::CANCELLED
            ? $line
            : $line.' '.route('events.show', $event);
    }
}
