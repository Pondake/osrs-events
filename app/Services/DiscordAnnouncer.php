<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Posting an event's news into the Discord channel it is organised in.
 *
 * This exists because email cannot do the job here and never will: Discord
 * login deliberately does not ask for an address (see DiscordController), so
 * roughly half of any clan has no inbox this app can reach. All of them are
 * in the same Discord channel, though — that is where the event was announced
 * in the first place — and an incoming webhook is one URL a host pastes once.
 *
 * Deliberately minimal: a sentence and a link, which is what a host would
 * have typed by hand. No embeds, no mentions, and nothing that can ping a
 * whole server — anything louder becomes a thing people mute.
 *
 * **Failure is never the user's problem.** A revoked webhook, a deleted
 * channel or Discord being down must not turn "pause my event" into a 500 —
 * the pause has already happened by the time this runs. Everything is caught
 * and logged; the caller is told whether it landed, and says so in the flash.
 */
class DiscordAnnouncer
{
    /** Discord's own limit on a webhook message body. */
    private const MAX_CONTENT = 2000;

    /**
     * The statuses that mean the webhook itself is gone, as opposed to
     * Discord having a bad minute.
     *
     * 404 is what a deleted webhook actually answers — measured, not
     * assumed; the guess going in was 401. 401 and 403 are here because a
     * token that no longer matches looks the same to a host: the URL in
     * their settings is dead either way and only they can replace it.
     *
     * Everything else is deliberately absent. A 429 means Discord is pacing
     * us, a 5xx means Discord is unwell, and a timeout means the network is
     * — none of those are the host's to fix, and telling them their webhook
     * is broken because Discord hiccupped is worse than saying nothing.
     */
    private const REVOKED_STATUSES = [401, 403, 404];

    /**
     * Short: a webhook that does not answer must not hold a request open.
     * The whole point of this is that it is a side effect of somebody's
     * button press.
     */
    private const TIMEOUT_SECONDS = 5;

    /**
     * Only Discord's own webhook endpoints.
     *
     * The URL is typed into a settings form by a host, and the app then makes
     * a POST to whatever it says — which is a server-side request forgery
     * primitive if anything at all is accepted. Validation on the form says
     * the same thing (BoardController::update); this is the half that cannot
     * be skipped by a request that never went through the form.
     *
     * **Both spellings of the path.** Discord's REST API is versioned in the
     * URL, so one webhook has two equally real addresses: `/api/webhooks/…`
     * and `/api/v10/webhooks/…`. The *Copy Webhook URL* button gives the
     * unversioned one, which is why this went unnoticed — but a URL that came
     * from anywhere else (Discord's own docs, a bot library, anything built
     * from an API response) carries the version, and refusing it told a host
     * to go and copy the URL they had already copied correctly. The version
     * number is left open rather than pinned to 10: Discord ships new ones,
     * and the part that actually matters for safety is the host allow-list
     * above plus the `webhooks/` segment.
     */
    public static function isValidUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        return ($parts['scheme'] ?? null) === 'https'
            && in_array($parts['host'] ?? null, ['discord.com', 'discordapp.com', 'ptb.discord.com', 'canary.discord.com'], true)
            && preg_match('#^/api/(v\d+/)?webhooks/#', $parts['path'] ?? '') === 1;
    }

    public function __construct(private readonly PushNotifier $push) {}

    /**
     * @return bool whether Discord accepted it — false covers "no webhook
     *              configured" as well as a failed post, because the caller
     *              only ever asks "did the channel hear about this"
     */
    public function announce(Event $event, string $message): bool
    {
        // The site-wide switch, checked here rather than at each call site:
        // this is the one place an outbound request is actually made, so it
        // is the one place that cannot be bypassed by a caller that forgot.
        if (! Setting::get('discord_webhooks_enabled')) {
            return false;
        }

        if (! self::isValidUrl($event->discord_webhook_url)) {
            return false;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->asJson()
                ->post($event->discord_webhook_url, [
                    'content' => mb_substr($message, 0, self::MAX_CONTENT),
                    // Nobody gets pinged by an automated status post. Without
                    // this, an event titled "@everyone bingo" would ping the
                    // server every time it was paused.
                    'allowed_mentions' => ['parse' => []],
                ]);

            if ($response->successful()) {
                $this->clearFailure($event);

                return true;
            }

            if (in_array($response->status(), self::REVOKED_STATUSES, true)) {
                $this->recordFailure($event);
            }

            return false;
        } catch (Throwable $error) {
            // Logged, not raised: the event has already been paused, resumed
            // or deleted by the time we get here, and failing the request
            // would undo nothing while looking like the action failed.
            report($error);

            return false;
        }
    }

    /**
     * Remember that this event's webhook is dead, and tell its hosts once.
     *
     * **Once is the whole point.** The flag is set on the transition, not on
     * every refusal — an event that announces four times a day with a dead
     * webhook would otherwise notify four times a day about the same broken
     * URL, which is exactly the chattiness the category catalogue exists to
     * prevent. A second push only becomes possible after a post lands again
     * and the flag clears, which is to say after somebody fixed it and it
     * broke a second time. That is worth hearing about.
     *
     * The write is unguarded on purpose: the caller is mid-pause, and a
     * failure to record a failure must not turn into a failure to pause.
     */
    private function recordFailure(Event $event): void
    {
        if ($event->discord_webhook_failed_at !== null) {
            return;
        }

        $event->forceFill(['discord_webhook_failed_at' => now()])->save();

        $hosts = $event->loadMissing('authors.user')->authors->pluck('user')->filter();

        if ($hosts->isEmpty()) {
            return;
        }

        $this->push->toUsers($hosts, new PushMessage(
            title: trans('notifications.push_discord_webhook_title'),
            body: trans('notifications.push_discord_webhook_body', ['event' => $event->title]),
            path: "/events/{$event->id}",
            category: NotificationCategory::DISCORD_WEBHOOK,
            tag: 'discord-webhook:'.$event->id,
        ));
    }

    /**
     * A post landed, so whatever was wrong is not wrong any more.
     *
     * Cleared on every success rather than only when a host edits the field:
     * a webhook can come back without anybody touching this app — Discord
     * recovers, a channel is un-archived — and a warning about a problem
     * that has gone away teaches people to ignore the warning.
     */
    private function clearFailure(Event $event): void
    {
        if ($event->discord_webhook_failed_at === null) {
            return;
        }

        $event->forceFill(['discord_webhook_failed_at' => null])->save();
    }
}
