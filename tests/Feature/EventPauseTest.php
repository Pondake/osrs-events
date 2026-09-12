<?php

namespace Tests\Feature;

use App\Events\Channels\EventChannelResolver;
use App\Models\AuditLog;
use App\Models\BingoCard;
use App\Models\Board;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PlayerBoard;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tile;
use App\Models\User;
use App\Notifications\EventStatusChanged;
use App\Services\BingoService;
use App\Services\DiscordAnnouncer;
use App\Support\AnnouncementTrigger;
use App\Support\EventCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Stopping an event, temporarily and permanently.
 *
 * Two things a host could not do at all: pausing did not exist, and deleting
 * existed only as a route the admin area had a button for — so the way to
 * halt a clan event mid-dispute was to edit its end date and hope, and the
 * way to get rid of one you created by mistake was to ask an admin.
 *
 * What is covered here is mostly the *edges* of a pause, because the middle
 * is easy: it must stop play without stopping reading, it must reach open
 * browsers through the live channel rather than on the next reload, it must
 * not lock a player into an event they can no longer leave, and it must not
 * mail thirty people twice because somebody double-clicked.
 */
class EventPauseTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $type = 'SNAKES_LADDERS', array $attributes = []): Event
    {
        // `paused_at` is lifted out and forced on, because it is deliberately
        // not fillable — Event::create() would drop it silently, which is the
        // behaviour an_ordinary_save_cannot_pause_an_event() relies on.
        $pausedAt = $attributes['paused_at'] ?? null;
        unset($attributes['paused_at']);

        $event = Event::create([
            'title' => "A {$type} event",
            'type' => $type,
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);

        if ($pausedAt !== null) {
            $event->forceFill(['paused_at' => $pausedAt])->save();
        }

        return $event;
    }

    /** A Snakes & Ladders event with a grid on it. */
    private function board(): Event
    {
        $event = $this->event();
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);

        foreach (range(0, 24) as $position) {
            Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        return $event->fresh();
    }

    private function host(Event $event, bool $owner = true): User
    {
        $host = User::factory()->create();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => $owner]);

        return $host;
    }

    /** Somebody who joined and can be reached by email. */
    private function participant(Event $event, ?string $email = null): User
    {
        $user = User::factory()->create($email ? ['email' => $email] : []);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id]);

        return $user;
    }

    private function pause(User $actor, Event $event, bool $paused = true, array $extra = [])
    {
        return $this->actingAs($actor)->patch("/events/{$event->id}/pause", ['paused' => $paused, ...$extra]);
    }

    // ------------------------------------------------------ pausing itself

    #[Test]
    public function a_host_can_pause_and_resume(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);

        $this->pause($host, $event)->assertRedirect();
        $this->assertNotNull($event->fresh()->paused_at);

        $this->pause($host, $event->fresh(), paused: false)->assertRedirect();
        $this->assertNull($event->fresh()->paused_at);
    }

    /**
     * A co-host can stop an event even though they cannot delete it. The
     * person who notices the problem is rarely the person whose name is on
     * the event.
     */
    #[Test]
    public function a_co_host_can_pause_but_a_stranger_cannot(): void
    {
        Notification::fake();
        $event = $this->event();
        $coHost = $this->host($event, owner: false);

        $this->pause($coHost, $event)->assertRedirect();
        $this->assertNotNull($event->fresh()->paused_at);

        $this->pause(User::factory()->create(), $event->fresh(), paused: false)->assertForbidden();
        $this->assertNotNull($event->fresh()->paused_at);
    }

    #[Test]
    public function pausing_is_written_to_the_audit_log(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);

        $this->pause($host, $event);
        $this->pause($host, $event->fresh(), paused: false);

        $this->assertSame(
            ['event.paused', 'event.resumed'],
            AuditLog::orderBy('created_at')->pluck('action')->all(),
        );
    }

    // -------------------------------------------------------- what it stops

    #[Test]
    public function a_paused_board_takes_no_rolls_and_no_tile_ticks(): void
    {
        $event = $this->board();
        $player = User::factory()->create();
        $tile = $event->board->tiles()->first();

        $event->forceFill(['paused_at' => Carbon::now()])->save();

        $this->actingAs($player)->post("/events/{$event->id}/roll")->assertSessionHas('board-save-error');
        $this->actingAs($player)->post("/events/{$event->id}/tiles/{$tile->id}/toggle")->assertSessionHas('board-save-error');

        $this->assertDatabaseCount('player_boards', 0);
        $this->assertDatabaseCount('completed_tiles', 0);
    }

    #[Test]
    public function a_paused_card_takes_no_claims(): void
    {
        $event = $this->event('BINGO');
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        app(BingoService::class)->ensureSquares($card);
        $square = $card->squares()->where('is_wildcard', false)->first();
        $player = User::factory()->create();

        $event->forceFill(['paused_at' => Carbon::now()])->save();

        $this->actingAs($player)
            ->post("/events/{$event->id}/bingo/squares/{$square->id}/claim")
            ->assertSessionHas('board-save-error');

        $this->assertDatabaseCount('bingo_completions', 0);
    }

    #[Test]
    public function a_paused_event_takes_no_new_participants(): void
    {
        $event = $this->event('BINGO', ['paused_at' => Carbon::now()]);
        $player = User::factory()->create();

        $this->actingAs($player)->post("/events/{$event->id}/join")->assertRedirect();

        $this->assertDatabaseCount('event_participants', 0);
    }

    /**
     * A race is entered through its own route rather than the join one, so
     * it needs saying separately — both go through the same service, which
     * is exactly the kind of thing that stops being true one refactor later.
     */
    #[Test]
    public function a_paused_race_cannot_be_entered(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['data' => ['skills' => []]])]);

        $event = $this->event('SKILL_RACE', [
            'metric' => 'mining',
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
            'paused_at' => Carbon::now(),
        ]);

        $this->actingAs(User::factory()->create())->post("/events/{$event->id}/enter")->assertRedirect();

        $this->assertDatabaseCount('event_participants', 0);
        $this->assertDatabaseCount('event_standings', 0);
    }

    /**
     * The host's side stays open. Clearing a queue of disputed claims is
     * frequently the reason the event was paused in the first place, so a
     * pause that also froze the review would be stopping the wrong half.
     */
    #[Test]
    public function a_host_can_still_review_claims_on_a_paused_event(): void
    {
        $event = $this->event('BINGO');
        $card = BingoCard::create(['event_id' => $event->id, 'size' => 3, 'requires_approval' => true]);
        app(BingoService::class)->ensureSquares($card);
        $square = $card->squares()->where('is_wildcard', false)->first();
        $host = $this->host($event);
        $player = $this->participant($event);

        $this->actingAs($player)->post("/events/{$event->id}/bingo/squares/{$square->id}/claim", ['proof_url' => 'https://i.imgur.com/x.png']);
        $claim = $square->completions()->firstOrFail();

        $event->forceFill(['paused_at' => Carbon::now()])->save();

        $this->actingAs($host)
            ->patch("/events/{$event->id}/bingo/claims/{$claim->id}", ['status' => 'APPROVED'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $claim->fresh()->status);
    }

    /**
     * The other half of that rule, and the more important one: being stuck
     * inside something that has stopped, with no way out until a host comes
     * back, is worse than the thing the pause was protecting.
     */
    #[Test]
    public function somebody_already_in_a_paused_event_can_still_leave(): void
    {
        $event = $this->event('BINGO');
        $player = $this->participant($event);

        $event->forceFill(['paused_at' => Carbon::now()])->save();

        $this->actingAs($player)->delete("/events/{$event->id}/join")->assertRedirect();

        $this->assertDatabaseCount('event_participants', 0);
    }

    /** A pause is usually the prelude to fixing something. */
    #[Test]
    public function a_host_can_still_edit_a_paused_event(): void
    {
        $event = $this->event(attributes: ['paused_at' => Carbon::now()]);
        $host = $this->host($event);

        $this->actingAs($host)
            ->patch("/events/{$event->id}", ['title' => 'Renamed while on hold'])
            ->assertRedirect();

        $this->assertSame('Renamed while on hold', $event->fresh()->title);
        $this->assertNotNull($event->fresh()->paused_at, 'an ordinary save must not resume the event');
    }

    /**
     * `paused_at` is not fillable, so the field cannot ride along inside an
     * ordinary settings save — pausing announces itself to everybody who
     * joined, and that is not something a rename should be able to do.
     */
    #[Test]
    public function an_ordinary_save_cannot_pause_an_event(): void
    {
        $event = $this->event();
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => 'Still running',
            'paused_at' => Carbon::now()->toIso8601String(),
        ])->assertRedirect();

        $this->assertNull($event->fresh()->paused_at);
    }

    // ---------------------------------------------------------- going live

    /**
     * The whole point of pausing rather than editing the end date: it has to
     * reach every open browser within seconds. The fingerprint is what makes
     * that happen, and it is read fresh from the database — so this asserts
     * against a channel holding the pre-pause instance, exactly as a live
     * connection does.
     */
    #[Test]
    public function pausing_changes_the_live_fingerprint(): void
    {
        $event = $this->event('BINGO');
        BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        $channel = app(EventChannelResolver::class)->for($event);

        $before = $channel->fingerprint($event);

        $event->forceFill(['paused_at' => Carbon::now()])->save();

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    #[Test]
    public function the_live_payload_carries_the_paused_state(): void
    {
        $event = $this->event('BINGO', ['paused_at' => Carbon::now()]);

        $this->assertNotNull(EventCard::for($event)['paused_at']);
        $this->assertNull(EventCard::for($this->event('BINGO'))['paused_at']);
    }

    /**
     * The stream the pause is announced on has to survive the pause.
     *
     * Obvious right up until somebody adds it: pausing stops rolls, claims
     * and new entries, and "stop the event doing things" reads like it should
     * include the live channel. If it ever did, every browser watching would
     * go silent at the moment of the pause and never hear the resume — which
     * is the one thing that makes a pause a pause rather than an ending.
     *
     * All three readers of an OPEN event, because the gate is
     * canSeeParticipants() and a pause is not a change of access.
     */
    #[Test]
    public function a_paused_event_still_streams_to_everyone_watching_it(): void
    {
        $event = $this->event('BINGO', ['paused_at' => Carbon::now()]);
        BingoCard::create(['event_id' => $event->id, 'size' => 3]);

        $this->get("/events/{$event->id}/stream")->assertOk();
        $this->actingAs($this->host($event))->get("/events/{$event->id}/stream")->assertOk();
        $this->actingAs($this->participant($event))->get("/events/{$event->id}/stream")->assertOk();
    }

    /**
     * Two changes, not one round trip nobody sees.
     *
     * pausing_changes_the_live_fingerprint() covers the way in. This is the
     * way back out, and it is the half that a player — who never pressed
     * anything and has no flash message — depends on entirely: the resume
     * reaches them through the fingerprint or it does not reach them at all.
     *
     * The resumed fingerprint returning to the pre-pause value is the correct
     * outcome, not an accident: a fingerprint is what the viewer reads, and a
     * resumed event reads exactly like one that was never paused.
     */
    #[Test]
    public function resuming_changes_the_live_fingerprint_back(): void
    {
        $event = $this->event('BINGO');
        BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        $channel = app(EventChannelResolver::class)->for($event);
        $host = $this->host($event);

        Notification::fake();

        $running = $channel->fingerprint($event);

        $this->pause($host, $event, extra: ['reason' => 'Back at 20:00.']);
        $paused = $channel->fingerprint($event->fresh());
        $this->assertNotSame($running, $paused);

        $this->pause($host, $event, paused: false);
        $this->assertNotSame($paused, $channel->fingerprint($event->fresh()));
        $this->assertSame($running, $channel->fingerprint($event->fresh()));
    }

    /**
     * A paused race must not keep pulling fresh numbers into its standings —
     * the pause would be a lie the moment it was lifted.
     */
    #[Test]
    public function the_standings_sync_skips_a_paused_race(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response(['data' => ['skills' => []]])]);

        $this->event('SKILL_RACE', [
            'metric' => 'mining',
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
            'paused_at' => Carbon::now(),
        ]);

        $this->artisan('events:sync-standings')->expectsOutputToContain('No skill races to sync.')->assertSuccessful();
    }

    // ------------------------------------------------------------- the mail

    #[Test]
    public function pausing_emails_the_people_who_joined(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $reachable = $this->participant($event, 'player@example.com');

        $this->pause($host, $event);

        Notification::assertSentTo($reachable, EventStatusChanged::class, fn ($notification) => $notification->change === EventStatusChanged::PAUSED);
    }

    /**
     * Discord login never asks for an email address (see DiscordController),
     * so most accounts have none. Those are skipped rather than failed on,
     * and the flash says how many were actually reached — a host announcing a
     * cancellation to thirty players is often announcing it to one.
     */
    #[Test]
    public function participants_without_an_email_address_are_skipped_and_counted(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $reachable = $this->participant($event, 'player@example.com');
        $unreachable = $this->participant($event);

        $response = $this->pause($host, $event);

        Notification::assertSentTo($reachable, EventStatusChanged::class);
        Notification::assertNotSentTo($unreachable, EventStatusChanged::class);
        $response->assertSessionHas('board-save', fn ($message) => str_contains($message, '1 of 2'));
    }

    #[Test]
    public function the_host_can_turn_the_email_off(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $this->participant($event, 'player@example.com');

        $this->pause($host, $event, extra: ['notify' => false]);

        Notification::assertNothingSent();
    }

    /** A host who joined their own event does not need mailing about it. */
    #[Test]
    public function the_host_is_not_mailed_about_their_own_click(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $host->update(['email' => 'host@example.com']);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $host->id]);

        $this->pause($host, $event);

        Notification::assertNothingSent();
    }

    /** A double-click must not send a second round of mail to everybody. */
    #[Test]
    public function pausing_an_already_paused_event_sends_nothing(): void
    {
        Notification::fake();
        $event = $this->event(attributes: ['paused_at' => Carbon::now()]);
        $host = $this->host($event);
        $this->participant($event, 'player@example.com');

        $this->pause($host, $event);

        Notification::assertNothingSent();
    }

    /**
     * The mail has to say something. Every string in it comes from
     * `lang/en.json` through `trans()`, and a key that is not there renders
     * as its own name — laravel-vue-i18n's echo-the-key behaviour has bitten
     * this project before, and nothing else in the suite reads the body.
     */
    #[Test]
    public function the_mail_reads_as_english_rather_than_as_translation_keys(): void
    {
        $reader = User::factory()->create(['email' => 'player@example.com']);

        $paused = (new EventStatusChanged(EventStatusChanged::PAUSED, 'Clan Bingo', 'https://osrs-events.test/e/1'))
            ->toMail($reader);

        $this->assertStringContainsString('Clan Bingo', $paused->subject);
        $this->assertStringNotContainsString('notifications.', $paused->subject);
        $this->assertStringNotContainsString('notifications.', implode(' ', $paused->introLines));
        $this->assertSame('https://osrs-events.test/e/1', $paused->actionUrl);

        // Nowhere to send anyone once the event is gone.
        $cancelled = (new EventStatusChanged(EventStatusChanged::CANCELLED, 'Clan Bingo'))->toMail($reader);

        $this->assertNull($cancelled->actionUrl);
        $this->assertStringContainsString('cancelled', $cancelled->subject);
    }

    // ------------------------------------------------- the host's own words

    #[Test]
    public function a_pause_reason_is_kept_while_paused_and_dropped_on_resume(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);

        $this->pause($host, $event, extra: ['reason' => 'Waiting on screenshots from team B.']);
        $this->assertSame('Waiting on screenshots from team B.', $event->fresh()->pause_reason);

        // Cleared rather than kept as history: it describes why the event is
        // stopped now, and a stale note on a running event is worse than none.
        $this->pause($host, $event->fresh(), paused: false);
        $this->assertNull($event->fresh()->pause_reason);
    }

    #[Test]
    public function the_reason_travels_with_the_mail(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $player = $this->participant($event, 'player@example.com');

        $this->pause($host, $event, extra: ['reason' => 'Back at 20:00.']);

        Notification::assertSentTo(
            $player,
            EventStatusChanged::class,
            fn ($notification) => $notification->reason === 'Back at 20:00.'
        );
    }

    /** An edited reason is an edited banner, so it has to wake open pages. */
    #[Test]
    public function changing_the_reason_changes_the_live_fingerprint(): void
    {
        $event = $this->event('BINGO');
        BingoCard::create(['event_id' => $event->id, 'size' => 3]);
        $channel = app(EventChannelResolver::class)->for($event);

        $event->forceFill(['paused_at' => Carbon::now(), 'pause_reason' => 'One moment'])->save();
        $before = $channel->fingerprint($event);

        $event->forceFill(['pause_reason' => 'Back at 20:00'])->save();

        $this->assertNotSame($before, $channel->fingerprint($event));
    }

    // ------------------------------------------------------------- Discord

    #[Test]
    public function pausing_posts_to_the_events_discord_channel(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response('', 204)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);
        $host = $this->host($event);

        $this->pause($host, $event, extra: ['reason' => 'Back at 20:00.']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.com/api/webhooks/123/abc'
                && str_contains($request['content'], 'has been paused')
                && str_contains($request['content'], 'Back at 20:00.')
                // Nobody gets pinged by a status post — an event titled
                // "@everyone bingo" must not ring the whole server.
                && $request['allowed_mentions'] === ['parse' => []];
        });
    }

    /**
     * The site-wide switch, which ships off. Until somebody has watched this
     * post to a real Discord server, a configured webhook must stay inert —
     * this is the app making an outbound request into a room full of people
     * who never asked it for anything.
     */
    #[Test]
    public function nothing_is_posted_while_the_site_switch_is_off(): void
    {
        Notification::fake();
        Http::fake();

        $this->assertFalse((bool) Setting::get('discord_webhooks_enabled'), 'the default has to be off');

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        $this->pause($this->host($event), $event);

        Http::assertNothingSent();
    }

    #[Test]
    public function an_event_without_a_webhook_posts_nothing(): void
    {
        Notification::fake();
        Http::fake();

        $event = $this->event();
        $this->pause($this->host($event), $event);

        Http::assertNothingSent();
    }

    /**
     * The pause has already happened by the time the webhook is called, so a
     * revoked URL or a Discord outage must not turn it into a failed request.
     */
    #[Test]
    public function a_failing_webhook_does_not_fail_the_pause(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response('gone', 404)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);
        $host = $this->host($event);

        $this->pause($host, $event)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNotNull($event->fresh()->paused_at);
    }

    /**
     * The named testcase, and the reason `allowed_mentions` is there at all.
     *
     * A title is free text a host types, and it is pasted into the message
     * body verbatim — Discord parses mentions out of the body, so "@everyone
     * bingo" is an event name on this site and a server-wide ping in that
     * channel. Both assertions are needed: suppressing the ping by mangling
     * the title would pass the second one alone, and would rename somebody's
     * event in the only place it gets read.
     */
    #[Test]
    public function an_event_titled_at_everyone_reaches_the_channel_without_pinging_it(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response('', 204)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event('SNAKES_LADDERS', ['title' => '@everyone bingo']);
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        $this->pause($this->host($event), $event);

        Http::assertSent(fn ($request) => str_contains($request['content'], '@everyone bingo')
            && $request['allowed_mentions'] === ['parse' => []]);
    }

    /**
     * The link is most of what these posts are for — the sentence says what
     * happened, the URL is how anybody acts on it — and Discord only unfurls
     * an absolute one. A relative path would render as literal text in the
     * channel and go nowhere.
     *
     * This is the half of "does the link resolve" a test can answer: that the
     * app emits a real absolute URL for the event. Whether the host behind it
     * answers is a question for a publicly reachable deploy, and lives in the
     * runbook in docs/backlog.md rather than here.
     */
    #[Test]
    public function the_post_carries_an_absolute_link_to_the_event(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response('', 204)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        $this->pause($this->host($event), $event);

        Http::assertSent(fn ($request) => str_contains(
            $request['content'],
            rtrim(config('app.url'), '/')."/events/{$event->id}",
        ));
    }

    /**
     * How this feature actually ends: a server admin deletes the webhook and
     * nobody tells the app. Discord answers 401 while the token is wrong and
     * 404 once the webhook itself is gone. The resume matters as much as the
     * pause — a host who cannot lift their own pause because a Discord
     * integration broke is strictly worse off than one who never had it.
     */
    #[Test]
    #[DataProvider('revokedWebhookResponses')]
    public function a_revoked_webhook_breaks_neither_the_pause_nor_the_resume(int $status, array $body): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response($body, $status)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);
        $host = $this->host($event);

        $this->pause($host, $event)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($event->fresh()->paused_at);

        $this->pause($host, $event, paused: false)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($event->fresh()->paused_at);
    }

    /** @return array<string, array{int, array<string, mixed>}> */
    public static function revokedWebhookResponses(): array
    {
        return [
            'token revoked' => [401, ['message' => 'Invalid Webhook Token', 'code' => 50027]],
            'webhook deleted' => [404, ['message' => 'Unknown Webhook', 'code' => 10015]],
        ];
    }

    /**
     * The failure that never reaches `$response->successful()` at all.
     *
     * Discord being unreachable, DNS failing, or the five-second timeout
     * expiring all raise out of the HTTP client rather than returning a
     * status — a different code path from a 404, and the one the `catch
     * (Throwable)` in DiscordAnnouncer exists for. Nothing else in the suite
     * entered it, so deleting that catch would have been silent.
     */
    #[Test]
    public function a_webhook_that_never_answers_does_not_break_the_pause(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        $this->pause($this->host($event), $event)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNotNull($event->fresh()->paused_at);
    }

    /**
     * Discord's rate limit, which this does not retry — and, more to the
     * point, does not claim to have beaten.
     *
     * A 429 is not `successful()`, so the post is dropped. That is defensible
     * for a status line nobody is waiting on, but only while the host is told:
     * the confirmation must not say it was posted to Discord when it was not.
     * Pinned here because "and it says so" is the part that would quietly rot
     * if the flash were ever simplified.
     */
    #[Test]
    public function a_rate_limited_post_is_dropped_and_not_reported_as_posted(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response(
            ['message' => 'You are being rate limited.', 'retry_after' => 1.5, 'global' => false],
            429,
        )]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        $this->pause($this->host($event), $event)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNotNull($event->fresh()->paused_at);

        // Asserted against the confirmation the host actually got, so this
        // cannot pass by there being no flash at all to look in.
        $flash = session('board-save');
        $this->assertStringContainsString(trans('events.paused_confirmed'), $flash);
        $this->assertStringNotContainsString(trans('events.notified_discord'), $flash);
    }

    /**
     * What a clan's volume looks like at the webhook, measured rather than
     * assumed.
     *
     * Discord allows a webhook 5 requests per 2 seconds, and this posts once
     * per finisher with no batching, no spacing and no throttle — deliberately
     * unlike the push half, where NotificationCategory carries a per-entity
     * floor. So a CONTINUE event where six teams get home inside a couple of
     * seconds is one over the ceiling, and the sixth line is the one that
     * disappears.
     *
     * This does not fail on that; it pins the shape, so the number stays a
     * decision rather than becoming a discovery. Whether it bites depends on
     * how bunched real finishes are, which is what the burst step in the
     * runbook in docs/backlog.md is for.
     */
    #[Test]
    public function nothing_batches_or_spaces_a_burst_of_posts(): void
    {
        Http::fake(['discord.com/*' => Http::response('', 204)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc']);

        $announcer = app(DiscordAnnouncer::class);

        foreach (range(1, 6) as $nth) {
            $announcer->announce($event, AnnouncementTrigger::FINISH, "Finisher {$nth}");
        }

        Http::assertSentCount(6);
    }

    /**
     * The app POSTs to whatever this field says, so anything accepted here is
     * a request the server will make on a host's say-so — a plain `url` rule
     * would be a server-side request forgery hole with a settings form in
     * front of it.
     */
    #[Test]
    public function only_a_real_discord_webhook_url_is_accepted(): void
    {
        $event = $this->event();
        $host = $this->host($event);

        $refused = [
            'http://127.0.0.1/admin',
            'https://evil.test/api/webhooks/1/x',
            'https://discord.com/api/guilds/1',
            // A version segment in the wrong place is NOT here on purpose:
            // `/api/webhooks/v10/1/x` is accepted, and always was, because
            // this checks the path's prefix and not the shape of the id and
            // token after it. Refusing it would mean validating those, which
            // is a bigger promise than "only Discord's own webhook endpoints"
            // and one more way to refuse a URL that actually works.
        ];

        foreach ($refused as $url) {
            $this->actingAs($host)
                ->patch("/events/{$event->id}", ['title' => 'Still here', 'discord_webhook_url' => $url])
                ->assertSessionHasErrors('discord_webhook_url');
        }

        // Both spellings Discord itself hands out. The versioned one was
        // refused until 2026-09-07, which told a host to go and copy a URL
        // they had already copied correctly — see DiscordAnnouncer.
        $accepted = [
            'https://discord.com/api/webhooks/1/x',
            'https://discord.com/api/v10/webhooks/1/x',
            'https://discord.com/api/v9/webhooks/1/x',
        ];

        foreach ($accepted as $url) {
            $this->actingAs($host)
                ->patch("/events/{$event->id}", ['title' => 'Still here', 'discord_webhook_url' => $url])
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * And the announcer's own half of the same rule, which is the one a
     * request that never went through the form meets — a seeder, tinker, or
     * anything added later that writes the column directly.
     */
    #[Test]
    public function a_versioned_webhook_url_is_posted_to_like_any_other(): void
    {
        Notification::fake();
        Http::fake(['discord.com/*' => Http::response('', 204)]);
        Setting::set('discord_webhooks_enabled', true);

        $event = $this->event();
        $event->forceFill(['discord_webhook_url' => 'https://discord.com/api/v10/webhooks/123/abc'])->save();

        $this->pause($this->host($event), $event);

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.com/api/v10/webhooks/123/abc');
    }

    // ---------------------------------------------------------- deleting

    #[Test]
    public function deleting_keeps_the_rows_and_hides_the_event(): void
    {
        Notification::fake();
        $event = $this->board();
        $host = $this->host($event);
        $player = $this->participant($event);
        PlayerBoard::create(['board_id' => $event->board->id, 'user_id' => $player->id, 'current_position' => 3]);

        $this->actingAs($host)->delete("/events/{$event->id}")->assertRedirect('/events');

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        // The whole reason for a soft delete: the cascade would have taken
        // every one of these with it.
        $this->assertDatabaseCount('player_boards', 1);
        $this->assertDatabaseCount('tiles', 25);
        $this->assertDatabaseCount('event_participants', 1);

        $this->actingAs($host)->get("/events/{$event->id}")->assertNotFound();
    }

    #[Test]
    public function deleting_tells_the_people_who_joined(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $player = $this->participant($event, 'player@example.com');

        $this->actingAs($host)->delete("/events/{$event->id}");

        Notification::assertSentTo(
            $player,
            EventStatusChanged::class,
            // Nowhere to send them: the page is gone. A button leading to a
            // 404 would be worse than no button.
            fn ($notification) => $notification->change === EventStatusChanged::CANCELLED && $notification->url === null,
        );
    }

    #[Test]
    public function deleting_can_be_kept_quiet(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $this->participant($event, 'player@example.com');

        $this->actingAs($host)->delete("/events/{$event->id}", ['notify' => false]);

        Notification::assertNothingSent();
    }

    #[Test]
    public function a_co_host_cannot_delete_the_event(): void
    {
        Notification::fake();
        $event = $this->event();
        $coHost = $this->host($event, owner: false);

        $this->actingAs($coHost)->delete("/events/{$event->id}")->assertForbidden();

        $this->assertNotSoftDeleted('events', ['id' => $event->id]);
    }

    /**
     * The undo the soft delete exists for. Admin-only, and reachable from the
     * one list that still shows a deleted event at all.
     */
    #[Test]
    public function an_admin_can_restore_a_deleted_event(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($host)->delete("/events/{$event->id}");
        $this->assertSoftDeleted('events', ['id' => $event->id]);

        $this->actingAs($admin)->post("/admin/events/{$event->id}/restore")->assertRedirect();

        $this->assertNotSoftDeleted('events', ['id' => $event->id]);
    }

    /**
     * Everybody who joined was told the event was cancelled. Leaving that as
     * the last word about an event that is running again is the one case
     * where saying nothing actively misinforms — so this one is not optional.
     */
    #[Test]
    public function restoring_tells_the_people_who_were_told_it_was_cancelled(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);
        $player = $this->participant($event, 'player@example.com');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($host)->delete("/events/{$event->id}");
        $this->actingAs($admin)->post("/admin/events/{$event->id}/restore");

        Notification::assertSentTo(
            $player,
            EventStatusChanged::class,
            fn ($notification) => $notification->change === EventStatusChanged::RESTORED
        );
    }

    /**
     * An admin deleting from the manage list stays on the manage list — the
     * only page a deleted event is still visible on, and the one with the
     * Restore button. A host goes to the hub instead, because the page they
     * were on no longer exists.
     */
    #[Test]
    public function an_admin_delete_returns_to_the_admin_list(): void
    {
        Notification::fake();
        $event = $this->event();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)
            ->from('/admin/events')
            ->delete("/admin/events/{$event->id}")
            ->assertRedirect('/admin/events');
    }

    #[Test]
    public function a_deleted_event_is_gone_from_the_public_lists(): void
    {
        Notification::fake();
        $event = $this->event();
        $host = $this->host($event);

        $this->actingAs($host)->delete("/events/{$event->id}");

        $this->assertSame(0, Event::count());
        $this->assertSame(1, Event::withTrashed()->count());
    }
}
