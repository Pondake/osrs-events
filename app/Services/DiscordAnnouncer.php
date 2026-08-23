<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
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
     */
    public static function isValidUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        return ($parts['scheme'] ?? null) === 'https'
            && in_array($parts['host'] ?? null, ['discord.com', 'discordapp.com', 'ptb.discord.com', 'canary.discord.com'], true)
            && str_starts_with($parts['path'] ?? '', '/api/webhooks/');
    }

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

            return $response->successful();
        } catch (Throwable $error) {
            // Logged, not raised: the event has already been paused, resumed
            // or deleted by the time we get here, and failing the request
            // would undo nothing while looking like the action failed.
            report($error);

            return false;
        }
    }
}
