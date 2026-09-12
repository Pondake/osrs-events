<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\Setting;
use App\Models\User;
use App\Services\DiscordAnnouncer;
use App\Services\RaceAnnouncer;
use App\Support\AnnouncementTrigger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Which announcements an event sends, and which it does not.
 *
 * The whole feature came out of one measurement: a bingo played to the end on
 * staging with a working webhook produced exactly one message. Nothing was
 * broken — claims never posted and a race posts nothing about racing — but
 * there was no way for a host to find that out, and no way to change it.
 *
 * So the two things worth pinning are the ones a host would be misled by: a
 * trigger they switched off must not fire, and a trigger that cannot apply to
 * their event type must never be offered or stored.
 */
class AnnouncementTriggerTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://discord.com/api/webhooks/123/abc';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('discord_webhooks_enabled', true);
        Http::fake(['discord.com/*' => Http::response('', 204)]);
    }

    private function event(string $type = 'BINGO', array $attributes = []): Event
    {
        $event = Event::create([
            'title' => 'A clan event',
            'type' => $type,
            'metric' => $type === 'SKILL_RACE' ? 'mining' : null,
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);

        $event->update(['discord_webhook_url' => self::URL]);

        return $event->fresh();
    }

    private function announce(Event $event, string $trigger): bool
    {
        return app(DiscordAnnouncer::class)->announce($event, $trigger, 'anything');
    }

    /**
     * Replace setUp's stub instead of adding to it.
     *
     * Http::fake() MERGES, and the first stub matching a URL wins — so a
     * second call in a test silently keeps returning setUp's 204 and the test
     * passes while proving nothing. Dropping the resolved factory is what
     * actually starts from nothing.
     */
    private function refake(array $stubs): void
    {
        $this->app->forgetInstance(Factory::class);
        Http::clearResolvedInstances();
        Http::fake($stubs);
    }

    // ------------------------------------------------------- the host's list

    /**
     * Null is "nobody has been to the tab", and must keep firing exactly what
     * fired before the column existed — otherwise this feature would have
     * gone quiet on every event that already had a webhook.
     */
    #[Test]
    public function an_event_nobody_has_configured_sends_the_defaults(): void
    {
        $event = $this->event();

        $this->assertNull($event->discord_announcements);
        $this->assertTrue($this->announce($event, AnnouncementTrigger::PAUSED));
        $this->assertTrue($this->announce($event, AnnouncementTrigger::FINISH));
    }

    #[Test]
    public function a_trigger_the_host_unticked_is_not_sent(): void
    {
        $event = $this->event();
        $event->update(['discord_announcements' => [AnnouncementTrigger::PAUSED]]);

        $this->assertTrue($this->announce($event->fresh(), AnnouncementTrigger::PAUSED));
        $this->assertFalse($this->announce($event->fresh(), AnnouncementTrigger::FINISH));

        Http::assertSentCount(1);
    }

    /**
     * An empty array is a host who ticked everything off, and has to stay a
     * different answer from never having looked — otherwise the one explicit
     * "send me nothing" in the app silently means "send me the defaults".
     */
    #[Test]
    public function an_empty_list_is_not_the_same_as_no_list(): void
    {
        $event = $this->event();
        $event->update(['discord_announcements' => []]);

        $this->assertFalse($this->announce($event->fresh(), AnnouncementTrigger::PAUSED));

        Http::assertNothingSent();
    }

    // ------------------------------------------------------ type boundaries

    /**
     * A race has no finishers and a board has no standings. Enforced in the
     * announcer rather than left to callers, so a new call site cannot post a
     * bingo finish into a skill race by passing the wrong constant.
     */
    #[Test]
    #[DataProvider('impossiblePairs')]
    public function a_trigger_that_cannot_apply_to_this_type_is_refused(string $type, string $trigger): void
    {
        // Forced on explicitly: this must fail on the type, not because the
        // host happened not to have it ticked.
        $event = $this->event($type);
        $event->update(['discord_announcements' => [$trigger]]);

        $this->assertFalse($this->announce($event->fresh(), $trigger));

        Http::assertNothingSent();
    }

    /** @return array<string, array{string, string}> */
    public static function impossiblePairs(): array
    {
        return [
            'a bingo cannot have a lead change' => ['BINGO', AnnouncementTrigger::RACE_LEAD],
            'a bingo has no closing standings' => ['BINGO', AnnouncementTrigger::RACE_FINAL],
            'a race has no finishers' => ['SKILL_RACE', AnnouncementTrigger::FINISH],
            'a race is not won by getting home' => ['SKILL_RACE', AnnouncementTrigger::WON],
        ];
    }

    #[Test]
    public function the_catalogue_offers_each_type_only_what_can_happen_to_it(): void
    {
        $this->assertContains(AnnouncementTrigger::FINISH, AnnouncementTrigger::forType('SNAKES_LADDERS'));
        $this->assertNotContains(AnnouncementTrigger::RACE_FINAL, AnnouncementTrigger::forType('SNAKES_LADDERS'));

        $this->assertContains(AnnouncementTrigger::RACE_FINAL, AnnouncementTrigger::forType('DROP_RACE'));
        $this->assertNotContains(AnnouncementTrigger::FINISH, AnnouncementTrigger::forType('DROP_RACE'));

        // The six host actions belong to everything — a pause is a pause.
        foreach (array_keys(Event::EVENT_TYPES) as $type) {
            $this->assertContains(AnnouncementTrigger::PAUSED, AnnouncementTrigger::forType($type));
        }
    }

    /**
     * Defaults follow how often a trigger can fire, not how interesting it
     * is. The two race triggers that can repeat ship off; the one that fires
     * once ships on.
     */
    #[Test]
    public function only_the_triggers_that_cannot_repeat_are_on_by_default(): void
    {
        $defaults = AnnouncementTrigger::defaultsFor('SKILL_RACE');

        $this->assertContains(AnnouncementTrigger::RACE_FINAL, $defaults);
        $this->assertNotContains(AnnouncementTrigger::RACE_LEAD, $defaults);
        $this->assertNotContains(AnnouncementTrigger::RACE_DIGEST, $defaults);
    }

    // --------------------------------------------------------- the throttle

    #[Test]
    public function a_lead_change_cannot_fire_twice_in_an_hour(): void
    {
        $event = $this->event('SKILL_RACE');
        $event->update(['discord_announcements' => [AnnouncementTrigger::RACE_LEAD]]);

        $this->assertTrue($this->announce($event->fresh(), AnnouncementTrigger::RACE_LEAD));
        $this->assertFalse($this->announce($event->fresh(), AnnouncementTrigger::RACE_LEAD));

        Http::assertSentCount(1);
    }

    /**
     * A post that never arrived must not buy the next hour of silence.
     *
     * Found by running the real thing rather than a fake: the window was
     * claimed on the way in, so a refused webhook or a Discord outage cost
     * the following hour too — and the comment above it claimed otherwise.
     */
    #[Test]
    public function a_failed_post_does_not_start_the_clock(): void
    {
        $this->refake(['discord.com/*' => Http::sequence()
            ->push('', 500)
            ->push('', 204)]);

        $event = $this->event('SKILL_RACE');
        $event->update(['discord_announcements' => [AnnouncementTrigger::RACE_LEAD]]);

        $this->assertFalse($this->announce($event->fresh(), AnnouncementTrigger::RACE_LEAD));
        $this->assertTrue($this->announce($event->fresh(), AnnouncementTrigger::RACE_LEAD));

        Http::assertSentCount(2);
    }

    /** A throttle is per event, not per app — two races do not gag each other. */
    #[Test]
    public function one_race_throttling_does_not_silence_another(): void
    {
        $first = $this->event('SKILL_RACE');
        $second = $this->event('SKILL_RACE');

        foreach ([$first, $second] as $event) {
            $event->update(['discord_announcements' => [AnnouncementTrigger::RACE_LEAD]]);
            $this->assertTrue($this->announce($event->fresh(), AnnouncementTrigger::RACE_LEAD));
        }

        Http::assertSentCount(2);
    }

    // ------------------------------------------------------ the race posts

    #[Test]
    public function the_closing_standings_name_the_top_three_in_order(): void
    {
        $event = $this->event('SKILL_RACE');
        $this->standing($event, 'Third', 300);
        $this->standing($event, 'Winner', 5000);
        $this->standing($event, 'Second', 900);
        $this->standing($event, 'Fourth', 10);

        $this->assertTrue(app(RaceAnnouncer::class)->final($event));

        Http::assertSent(function ($request) {
            $body = $request->data()['content'];

            return str_contains($body, '1. Winner')
                && str_contains($body, '2. Second')
                && str_contains($body, '3. Third')
                && ! str_contains($body, 'Fourth');
        });
    }

    /**
     * A row that never measured is unknown, not last. Naming it on a podium
     * would report a zero somebody never scored.
     */
    #[Test]
    public function an_unsynced_entrant_is_left_off_the_podium(): void
    {
        $event = $this->event('SKILL_RACE');
        $this->standing($event, 'Measured', 10);
        $this->standing($event, 'Broken', 999, syncError: 'not found');

        app(RaceAnnouncer::class)->final($event);

        Http::assertSent(fn ($request) => ! str_contains($request->data()['content'], 'Broken'));
    }

    #[Test]
    public function a_race_with_nothing_measured_says_nothing(): void
    {
        $event = $this->event('SKILL_RACE');

        $this->assertFalse(app(RaceAnnouncer::class)->final($event));

        Http::assertNothingSent();
    }

    /**
     * The first leader is not an overtake — nobody was passed — and neither
     * is the same person still leading.
     */
    #[Test]
    public function a_lead_post_needs_an_actual_change_of_hands(): void
    {
        $event = $this->event('SKILL_RACE');
        $event->update(['discord_announcements' => [AnnouncementTrigger::RACE_LEAD]]);
        $was = $this->standing($event, 'Old', 10);
        $now = $this->standing($event, 'New', 20);

        $announcer = app(RaceAnnouncer::class);

        $this->assertFalse($announcer->leadChanged($event, [], [$now->user_id => 1]));
        $this->assertFalse($announcer->leadChanged($event, [$was->user_id => 1], [$was->user_id => 1]));

        Http::assertNothingSent();

        $this->assertTrue($announcer->leadChanged($event, [$was->user_id => 1], [$now->user_id => 1]));

        Http::assertSent(fn ($request) => str_contains($request->data()['content'], 'New'));
    }

    private function standing(Event $event, string $username, int $gained, ?string $syncError = null): EventStanding
    {
        return EventStanding::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create()->id,
            'username' => $username,
            'gained' => $gained,
            'sync_error' => $syncError,
            'synced_at' => $syncError === null ? now() : null,
        ]);
    }
}
