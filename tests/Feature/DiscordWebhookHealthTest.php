<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Services\DiscordAnnouncer;
use App\Services\WebPushService;
use App\Support\AnnouncementTrigger;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What happens when an event's Discord webhook stops working.
 *
 * `DiscordAnnouncer` swallows a failed post on purpose — the pause has
 * already happened by the time it runs, and failing the request would undo
 * nothing. Run against a real server on 2026-09-07 that turned out to mean
 * *nobody ever finds out*: Discord answers a deleted webhook with 404, which
 * is a perfectly ordinary response and not a `Throwable`, so the `catch` that
 * calls `report()` was never reached. No log line, no word to the host, and
 * an event that silently stopped announcing for the rest of its run.
 *
 * What is covered here is the narrow thing that fixes: a webhook that is
 * *gone* is remembered and said out loud once, while a webhook that merely
 * had a bad minute is still swallowed exactly as before. Getting that
 * distinction wrong in either direction is worse than the original silence —
 * a host told their webhook is broken because Discord rate-limited us will
 * replace a URL that was fine.
 */
class DiscordWebhookHealthTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://discord.com/api/webhooks/123/abc';

    private RecordingWebPushForWebhooks $push;

    protected function setUp(): void
    {
        parent::setUp();

        // The real sender posts through the library's own Guzzle client,
        // which Http::preventStrayRequests cannot see — see
        // PushNotificationTest for the full reasoning behind this seam.
        $this->push = new RecordingWebPushForWebhooks;
        $this->app->instance(WebPushService::class, $this->push);

        Setting::set('discord_webhooks_enabled', true);
    }

    private function eventWithWebhook(): Event
    {
        $event = Event::create([
            'title' => 'A clan event',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $event->update(['discord_webhook_url' => self::URL]);

        return $event->fresh();
    }

    private function host(Event $event): User
    {
        $host = User::factory()->create();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        PushSubscription::create([
            'user_id' => $host->id,
            'endpoint' => 'https://push.example.test/'.$host->id,
            'public_key' => 'p256dh-'.$host->id,
            'auth_token' => 'auth-'.$host->id,
        ]);

        return $host;
    }

    private function announce(Event $event): bool
    {
        // PAUSED because it fires for every type and is on by default, so
        // nothing here is testing the trigger gate by accident.
        return app(DiscordAnnouncer::class)->announce($event, AnnouncementTrigger::PAUSED, 'anything');
    }

    // ------------------------------------------------- the webhook is gone

    /**
     * 404 is what a deleted webhook really answers — measured, not assumed.
     * 401 and 403 are here because to a host they mean the same thing: the
     * URL in their settings is dead and only they can replace it.
     */
    #[Test]
    #[DataProvider('deadWebhookStatuses')]
    public function a_dead_webhook_is_remembered_and_said_out_loud_once(int $status): void
    {
        Http::fake(['discord.com/*' => Http::response(['message' => 'nope'], $status)]);

        $event = $this->eventWithWebhook();
        $host = $this->host($event);

        $this->assertFalse($this->announce($event));

        $event->refresh();
        $this->assertNotNull($event->discord_webhook_failed_at);

        $this->assertCount(1, $this->push->sent);
        $this->assertSame([$host->id], $this->push->sent[0]['users']);
        $this->assertSame(
            NotificationCategory::DISCORD_WEBHOOK,
            $this->push->sent[0]['message']->category
        );
    }

    /** @return array<string, array{int}> */
    public static function deadWebhookStatuses(): array
    {
        return [
            'token revoked' => [401],
            'forbidden' => [403],
            'webhook deleted' => [404],
        ];
    }

    /**
     * The reason the flag exists rather than a bare send.
     *
     * An event that announces four times a day with a dead webhook would
     * otherwise notify four times a day about the same broken URL — which is
     * exactly the chattiness NotificationCategory exists to prevent, and the
     * fastest way to have a host switch every category off.
     */
    #[Test]
    public function a_second_failure_says_nothing(): void
    {
        Http::fake(['discord.com/*' => Http::response(['message' => 'nope'], 404)]);

        $event = $this->eventWithWebhook();
        $this->host($event);

        $this->announce($event);
        $first = $event->fresh()->discord_webhook_failed_at;

        $this->announce($event->fresh());
        $this->announce($event->fresh());

        $this->assertCount(1, $this->push->sent);
        // And the timestamp still says when it FIRST broke, not when it last
        // refused — "dead for a fortnight" is the useful reading.
        $this->assertTrue($first->equalTo($event->fresh()->discord_webhook_failed_at));
    }

    /**
     * A webhook can come back without anybody touching this app — Discord
     * recovers, a channel is un-archived. A warning about a problem that has
     * gone away teaches people to ignore warnings.
     */
    #[Test]
    public function a_post_that_lands_clears_the_flag_and_the_banner_can_return(): void
    {
        // One sequence rather than three Http::fake() calls: a second fake
        // for the same pattern does not replace the first, so the run would
        // stay on 404 throughout and the "it recovered" half would never
        // actually be exercised.
        Http::fake(['discord.com/*' => Http::sequence()
            ->push(['message' => 'nope'], 404)
            ->push('', 204)
            ->push(['message' => 'nope'], 404)]);

        $event = $this->eventWithWebhook();
        $this->host($event);

        $this->announce($event);
        $this->assertNotNull($event->fresh()->discord_webhook_failed_at);

        $this->assertTrue($this->announce($event->fresh()));
        $this->assertNull($event->fresh()->discord_webhook_failed_at);

        // Broken a second time: the banner comes back, because the banner
        // reads the flag and the flag was genuinely cleared in between.
        $this->announce($event->fresh());
        $this->assertNotNull($event->fresh()->discord_webhook_failed_at);

        // The push does NOT come back, and that is the catalogue working
        // rather than a gap. DISCORD_WEBHOOK carries a day-long per-entity
        // throttle keyed on this event's tag, so break-fix-break inside an
        // afternoon reaches a host's phone once. The screen they are already
        // looking at has said it twice by then.
        $this->assertCount(1, $this->push->sent);
    }

    // --------------------------------------------- Discord having a moment

    /**
     * The half that matters most, and the one a looser rule would break.
     *
     * None of these mean the host's URL is wrong: 429 is Discord pacing us,
     * 5xx is Discord unwell, and a raised exception is the network or the
     * five-second timeout. Telling a host to replace a working webhook
     * because of any of them is worse than the silence this feature
     * replaced.
     */
    #[Test]
    #[DataProvider('transientFailures')]
    public function a_transient_failure_is_still_swallowed(callable $fake): void
    {
        Http::fake(['discord.com/*' => $fake()]);

        $event = $this->eventWithWebhook();
        $this->host($event);

        $this->assertFalse($this->announce($event));

        $this->assertNull($event->fresh()->discord_webhook_failed_at);
        $this->assertSame([], $this->push->sent);
    }

    /** @return array<string, array{callable}> */
    public static function transientFailures(): array
    {
        return [
            'rate limited' => [fn () => fn () => Http::response(['retry_after' => 1], 429)],
            'discord unwell' => [fn () => fn () => Http::response('', 503)],
            'never answers' => [fn () => fn () => throw new ConnectionException('cURL error 28: Operation timed out')],
        ];
    }

    /**
     * Nothing is recorded when no request was made at all. Both of these
     * already returned false; the point is that "we did not try" must not
     * read as "your webhook is broken".
     */
    #[Test]
    public function a_post_that_never_leaves_marks_nothing(): void
    {
        Http::fake(['discord.com/*' => Http::response('', 204)]);

        $event = $this->eventWithWebhook();
        $this->host($event);

        Setting::set('discord_webhooks_enabled', false);
        $this->assertFalse($this->announce($event));
        $this->assertNull($event->fresh()->discord_webhook_failed_at);

        Setting::set('discord_webhooks_enabled', true);
        $event->forceFill(['discord_webhook_url' => 'https://example.test/not-discord'])->save();
        $this->assertFalse($this->announce($event->fresh()));
        $this->assertNull($event->fresh()->discord_webhook_failed_at);

        $this->assertSame([], $this->push->sent);
    }

    /** An event nobody hosts cannot be told, and must not blow up trying. */
    #[Test]
    public function an_event_without_a_host_is_still_flagged(): void
    {
        Http::fake(['discord.com/*' => Http::response(['message' => 'nope'], 404)]);

        $event = $this->eventWithWebhook();

        $this->assertFalse($this->announce($event));
        $this->assertNotNull($event->fresh()->discord_webhook_failed_at);
        $this->assertSame([], $this->push->sent);
    }

    // ------------------------------------------------------- what the page sees

    /**
     * The banner is about a settings field only a host can open, so to
     * anybody else it would be a warning about something they cannot act on.
     * Withheld the same way the webhook URL beside it is.
     */
    #[Test]
    public function only_an_editor_is_told_on_the_event_page(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response(['message' => 'nope'], 404)]);

        $event = $this->eventWithWebhook();
        $host = $this->host($event);
        $this->announce($event);

        $this->actingAs($host)
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->whereNot('webhookFailedAt', null));

        $this->actingAs(User::factory()->create())
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('webhookFailedAt', null));
    }
}

/**
 * Records what it was asked to deliver instead of sending it.
 *
 * A local copy rather than PushNotificationTest's: that one lives in a file
 * whose name does not match its class, so PSR-4 never autoloads it and
 * reaching for it here would pass or fail depending on which test file ran
 * first.
 */
class RecordingWebPushForWebhooks extends WebPushService
{
    public array $sent = [];

    public function isConfigured(): bool
    {
        return true;
    }

    public function send(Collection $subscriptions, PushMessage $message): array
    {
        if ($subscriptions->isEmpty()) {
            return parent::emptyResult();
        }

        $this->sent[] = [
            'message' => $message,
            'users' => $subscriptions->pluck('user_id')->unique()->values()->all(),
        ];

        return ['sent' => 1, 'expired' => 0, 'failed' => 0, 'skipped' => 0];
    }
}
