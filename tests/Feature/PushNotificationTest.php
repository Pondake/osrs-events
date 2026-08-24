<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\BingoCompletion;
use App\Models\BingoSquare;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\BingoNotifier;
use App\Services\PushNotifier;
use App\Services\WebPushService;
use App\Support\NotificationCategory;
use App\Support\PushMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who gets a push, and — far more often — who does not.
 *
 * Nothing here sends anything. `minishlink/web-push` posts through its own
 * Guzzle client, which TestCase's Http::preventStrayRequests cannot see, so a
 * test that exercised the real send path would quietly reach the internet.
 * WebPushService is swapped for a recorder instead, which is also the right
 * seam: the interesting rules — preference, opt-out, throttle, audience — all
 * live above it, and the encryption below it is the library's problem.
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private RecordingWebPush $push;

    protected function setUp(): void
    {
        parent::setUp();

        $this->push = new RecordingWebPush;
        $this->app->instance(WebPushService::class, $this->push);
    }

    private function subscriber(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/'.$user->id,
            'public_key' => 'p256dh-'.$user->id,
            'auth_token' => 'auth-'.$user->id,
        ]);

        return $user;
    }

    private function message(string $category, string $tag = 'test'): PushMessage
    {
        return new PushMessage('Title', 'Body', '/events/1', $category, $tag);
    }

    // -----------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------

    /**
     * The endpoint is the identity. A browser re-posts the same subscription
     * on every load (that is what heals a row the server has lost), so an
     * insert here would mean a row per page view and a notification arriving
     * as many times as the app had been opened.
     */
    #[Test]
    public function subscribing_twice_with_the_same_endpoint_keeps_one_row(): void
    {
        $user = User::factory()->create();

        $payload = [
            'endpoint' => 'https://push.example.test/abc',
            'keys' => ['p256dh' => 'key-one', 'auth' => 'auth-one'],
        ];

        $this->actingAs($user)->postJson('/push/subscriptions', $payload)->assertOk();
        $this->actingAs($user)
            ->postJson('/push/subscriptions', [...$payload, 'keys' => ['p256dh' => 'key-two', 'auth' => 'auth-two']])
            ->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertSame('key-two', PushSubscription::first()->public_key);
    }

    /**
     * The off switch has to survive the page reload that follows it.
     *
     * Unsubscribing drops the browser's subscription but leaves the OS
     * permission granted — which is exactly the state the silent opt-in reads
     * as "granted, so subscribe". Without the stored flag the toggle would
     * undo itself immediately and appear broken.
     */
    #[Test]
    public function unsubscribing_records_an_explicit_opt_out(): void
    {
        $user = $this->subscriber();

        $this->actingAs($user)->deleteJson('/push/subscriptions', ['endpoint' => null])->assertOk();

        $this->assertNotNull($user->refresh()->push_opted_out_at);
        $this->assertSame(0, PushSubscription::count());
        $this->assertFalse($user->wantsNotification(NotificationCategory::CLAIM_REVIEWED));
    }

    /** Turning it back on is the opposite of the off switch, so it clears it. */
    #[Test]
    public function subscribing_again_clears_the_opt_out(): void
    {
        $user = $this->subscriber(['push_opted_out_at' => now()]);

        $this->actingAs($user)->postJson('/push/subscriptions', [
            'endpoint' => 'https://push.example.test/back',
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ])->assertOk();

        $this->assertNull($user->refresh()->push_opted_out_at);
    }

    /** Encryption material for one device is of no use to any client. */
    #[Test]
    public function the_settings_page_never_exposes_subscription_keys(): void
    {
        $user = $this->subscriber();

        $response = $this->actingAs($user)->get('/settings/notifications');

        $response->assertOk();
        $response->assertDontSee('p256dh-'.$user->id);
        $response->assertDontSee('auth-'.$user->id);
        // Not even the endpoint: it is the address a push is delivered to.
        $response->assertDontSee('push.example.test');
    }

    // -----------------------------------------------------------------
    // Preferences
    // -----------------------------------------------------------------

    /**
     * A category nobody has an opinion about falls back to the catalogue,
     * and the catalogue is where "off by default" is decided.
     */
    #[Test]
    public function defaults_come_from_the_catalogue_until_someone_says_otherwise(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->wantsNotification(NotificationCategory::CLAIM_REVIEWED));
        $this->assertFalse($user->wantsNotification(NotificationCategory::ROLLS_AVAILABLE));
    }

    #[Test]
    public function a_category_switched_off_is_not_sent(): void
    {
        $user = $this->subscriber([
            'notification_preferences' => [NotificationCategory::CLAIM_REVIEWED => false],
        ]);

        app(PushNotifier::class)->toUser($user, $this->message(NotificationCategory::CLAIM_REVIEWED));

        $this->assertSame([], $this->push->sent);
    }

    #[Test]
    public function an_opt_in_category_is_sent_once_switched_on(): void
    {
        $user = $this->subscriber([
            'notification_preferences' => [NotificationCategory::ROLLS_AVAILABLE => true],
        ]);

        app(PushNotifier::class)->toUser($user, $this->message(NotificationCategory::ROLLS_AVAILABLE));

        $this->assertCount(1, $this->push->sent);
    }

    /** An unknown key from a stale cached page must not write itself in. */
    #[Test]
    public function saving_preferences_ignores_keys_the_catalogue_does_not_know(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/notifications', [
            'preferences' => [
                NotificationCategory::RANK_CHANGE => true,
                'not_a_real_category' => true,
            ],
        ]);

        $stored = $user->refresh()->notification_preferences;

        $this->assertArrayHasKey(NotificationCategory::RANK_CHANGE, $stored);
        $this->assertArrayNotHasKey('not_a_real_category', $stored);
    }

    // -----------------------------------------------------------------
    // Throttle
    // -----------------------------------------------------------------

    /**
     * A per-entity floor, not a global rate. Ten claims landing in a host's
     * queue inside a minute are one notification; the point is that the host
     * is not buzzed ten times, not that the app goes quiet.
     */
    #[Test]
    public function a_throttled_category_says_it_once_per_entity(): void
    {
        $user = $this->subscriber();
        $notifier = app(PushNotifier::class);

        $notifier->toUser($user, $this->message(NotificationCategory::REVIEW_QUEUE, 'review:event-a'));
        $notifier->toUser($user, $this->message(NotificationCategory::REVIEW_QUEUE, 'review:event-a'));

        $this->assertCount(1, $this->push->sent);
    }

    /** Two different events are two different things to be told about. */
    #[Test]
    public function the_throttle_does_not_leak_between_entities(): void
    {
        $user = $this->subscriber();
        $notifier = app(PushNotifier::class);

        $notifier->toUser($user, $this->message(NotificationCategory::REVIEW_QUEUE, 'review:event-a'));
        $notifier->toUser($user, $this->message(NotificationCategory::REVIEW_QUEUE, 'review:event-b'));

        $this->assertCount(2, $this->push->sent);
    }

    /**
     * The throttle protects people from noise; it must not swallow the one
     * message that got through. Marking before the send would silence the
     * next hour on the strength of a push that never happened.
     */
    #[Test]
    public function a_send_that_reached_nobody_does_not_start_the_throttle(): void
    {
        $user = $this->subscriber();
        $this->push->result = ['sent' => 0, 'expired' => 0, 'failed' => 0, 'skipped' => 1];

        $notifier = app(PushNotifier::class);
        $notifier->toUser($user, $this->message(NotificationCategory::REVIEW_QUEUE, 'review:event-a'));

        $this->push->result = ['sent' => 1, 'expired' => 0, 'failed' => 0, 'skipped' => 0];
        $notifier->toUser($user, $this->message(NotificationCategory::REVIEW_QUEUE, 'review:event-a'));

        $this->assertCount(2, $this->push->sent);
    }

    /** An expired row is not an address any more. */
    #[Test]
    public function expired_subscriptions_are_not_sent_to(): void
    {
        $user = $this->subscriber();
        PushSubscription::query()->update(['expired_at' => now()]);

        app(PushNotifier::class)->toUser($user, $this->message(NotificationCategory::CLAIM_REVIEWED));

        $this->assertSame([], $this->push->sent);
    }

    // -----------------------------------------------------------------
    // Audience
    // -----------------------------------------------------------------

    /**
     * Participants, not everyone with access. A public event is readable by
     * the whole internet, and "you looked at this once" is not a reason to
     * buzz somebody's phone — the same rule the email side applies.
     */
    #[Test]
    public function only_participants_hear_about_an_event(): void
    {
        $event = Event::create(['title' => 'Race', 'type' => 'SKILL_RACE', 'metric' => 'mining', 'mode' => 'SOLO']);

        $player = $this->subscriber();
        $bystander = $this->subscriber();

        EventParticipant::create(['event_id' => $event->id, 'user_id' => $player->id]);

        app(PushNotifier::class)->toParticipants($event, $this->message(NotificationCategory::EVENT_STATUS, 'status:'.$event->id));

        $this->assertCount(1, $this->push->sent);
        $this->assertSame([$player->id], $this->push->sent[0]['users']);
        $this->assertNotContains($bystander->id, $this->push->sent[0]['users']);
    }

    /** Telling somebody the outcome of their own click reads as a bug. */
    #[Test]
    public function a_host_is_not_told_about_the_claim_they_just_reviewed(): void
    {
        $event = Event::create(['title' => 'Bingo', 'type' => 'BINGO', 'mode' => 'SOLO']);
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3, 'requires_approval' => true]);
        $square = BingoSquare::create(['bingo_card_id' => $card->id, 'position' => 0, 'title_override' => 'Fire cape', 'points' => 25]);

        $host = $this->subscriber();

        $completion = BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $host->id,
            'marked_by' => $host->id,
            'status' => 'APPROVED',
        ]);

        app(BingoNotifier::class)->reviewed($event, $completion->load('square', 'markedBy'), $host);

        $this->assertSame([], $this->push->sent);
    }

    /** The claimant is who was waiting, and the note is why it is useful. */
    #[Test]
    public function a_rejection_carries_the_hosts_note(): void
    {
        $event = Event::create(['title' => 'Bingo', 'type' => 'BINGO', 'mode' => 'SOLO']);
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3, 'requires_approval' => true]);
        $square = BingoSquare::create(['bingo_card_id' => $card->id, 'position' => 0, 'title_override' => 'Fire cape', 'points' => 25]);

        $host = User::factory()->create();
        $player = $this->subscriber();

        $completion = BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $player->id,
            'marked_by' => $player->id,
            'status' => 'REJECTED',
            'review_note' => 'Screenshot shows the wrong account',
        ]);

        app(BingoNotifier::class)->reviewed($event, $completion->load('square', 'markedBy'), $host);

        $this->assertCount(1, $this->push->sent);
        $this->assertStringContainsString('wrong account', $this->push->sent[0]['message']->body);
    }

    // -----------------------------------------------------------------
    // Failing safely
    // -----------------------------------------------------------------

    /**
     * A notification that cannot be sent must never cost more than the
     * notification. Missing VAPID keys is a normal state — a fresh clone, a
     * fresh deploy — so it reports "skipped" rather than throwing, which is
     * what lets the settings page name the problem instead of returning 500.
     */
    #[Test]
    public function an_unconfigured_server_skips_instead_of_throwing(): void
    {
        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

        $this->subscriber();

        $service = new WebPushService;
        $subscriptions = PushSubscription::query()->get();

        $result = $service->send($subscriptions, $this->message(NotificationCategory::CLAIM_REVIEWED));

        $this->assertSame(1, $result['skipped']);
        $this->assertSame(0, $result['sent']);
    }

    #[Test]
    public function the_settings_page_reports_an_unconfigured_server(): void
    {
        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

        // The real service, not the recorder from setUp: this test is about
        // what the page says when the SERVER cannot send, and a stand-in that
        // always reports itself configured would answer the wrong question.
        $this->app->instance(WebPushService::class, new WebPushService);

        $this->actingAs(User::factory()->create())
            ->get('/settings/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('serverConfigured', false));
    }
}

/**
 * Stands in for the real sender and records what it was asked to deliver.
 *
 * Extends rather than implements: WebPushService is a concrete class with
 * static helpers the callers use, and introducing an interface for one test
 * double would put an abstraction in the app to serve the test suite.
 */
class RecordingWebPush extends WebPushService
{
    public array $sent = [];

    public array $result = ['sent' => 1, 'expired' => 0, 'failed' => 0, 'skipped' => 0];

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

        return $this->result;
    }
}
