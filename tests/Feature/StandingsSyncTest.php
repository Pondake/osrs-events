<?php

namespace Tests\Feature;

use App\Events\Channels\EventChannelResolver;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Is what I am looking at true?"
 *
 * A host who has just moved the dates or changed the metric is reading a
 * table measured against the old ones, and the scheduled sync runs on its own
 * clock — so the honest answer was "wait and see". This is the button that
 * answers it, and these are the two things it has to get right: only a host
 * may spend somebody else's API budget, and a name that came back empty has
 * to be said out loud rather than counted.
 */
class StandingsSyncTest extends TestCase
{
    use RefreshDatabase;

    private function race(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Skill of the month',
            'type' => 'SKILL_RACE',
            'metric' => 'mining',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            'start_date' => Carbon::now()->subWeek(),
            'end_date' => Carbon::now()->addWeek(),
            ...$attributes,
        ]);
    }

    private function host(Event $event): User
    {
        $host = User::factory()->create();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        return $host;
    }

    private function entrant(Event $event, string $name): EventStanding
    {
        return EventStanding::create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create(['osrs_username' => $name])->id,
            'username' => $name,
        ]);
    }

    /**
     * An entrant whose numbers have actually been read once.
     *
     * The distinction the staleness rule turns on: a row that exists holds no
     * measurement until a sync has put one in it, and only a measurement can
     * go out of date.
     */
    private function readEntrant(Event $event, string $name): EventStanding
    {
        $row = $this->entrant($event, $name);
        $row->forceFill(['gained' => 4200, 'synced_at' => Carbon::now()])->save();

        return $row;
    }

    /** Wise Old Man's shape for a metric that gained something. */
    private function gains(int $gained): array
    {
        return ['data' => ['skills' => ['mining' => [
            'experience' => ['start' => 1000, 'end' => 1000 + $gained, 'gained' => $gained],
        ]]]];
    }

    #[Test]
    public function a_host_pulls_fresh_numbers_and_is_told_how_many(): void
    {
        Http::fake(['*wiseoldman.net*' => Http::response($this->gains(4200))]);

        $event = $this->race();
        $this->entrant($event, 'Pondake');

        $this->actingAs($this->host($event))
            ->post("/events/{$event->id}/standings/sync")
            ->assertRedirect()
            ->assertSessionHas('board-save');

        $row = EventStanding::firstOrFail();
        $this->assertSame(4200, $row->gained);
        $this->assertNotNull($row->synced_at);
        $this->assertNull($row->sync_error);
    }

    /**
     * The names, not just a count. "Updated 1 of 2" is a status; "no data came
     * back for Not A Player" is something a host can go and fix, and fixing it
     * is the only reason to press the button twice.
     */
    #[Test]
    public function an_entrant_wise_old_man_cannot_measure_is_named(): void
    {
        Http::fake(['*wiseoldman.net*' => Http::response(['data' => ['skills' => []]])]);

        $event = $this->race();
        $this->entrant($event, 'Not A Player');

        $this->actingAs($this->host($event))
            ->post("/events/{$event->id}/standings/sync")
            ->assertSessionHas('board-save-error', fn ($message) => str_contains($message, 'Not A Player'));

        $this->assertSame('not_tracked', EventStanding::firstOrFail()->sync_error);
    }

    /** Every press is one outbound request per entrant, on somebody else's API. */
    #[Test]
    public function only_a_host_may_spend_the_api_budget(): void
    {
        Http::fake(['*wiseoldman.net*' => Http::response($this->gains(1))]);

        $event = $this->race();
        $this->entrant($event, 'Pondake');

        $this->actingAs(User::factory()->create())
            ->post("/events/{$event->id}/standings/sync")
            ->assertForbidden();

        Http::assertNothingSent();
    }

    /** A paused race is a race whose scoreboard must not move. */
    #[Test]
    public function a_paused_race_refuses_the_sync(): void
    {
        Http::fake(['*wiseoldman.net*' => Http::response($this->gains(1))]);

        $event = $this->race();
        $event->forceFill(['paused_at' => Carbon::now()])->save();
        $this->entrant($event, 'Pondake');

        $this->actingAs($this->host($event))
            ->post("/events/{$event->id}/standings/sync")
            ->assertSessionHas('board-save-error', trans('events.paused_notice'));

        Http::assertNothingSent();
    }

    // ------------------------------------------------------- going stale

    /**
     * A standing is a measurement over a window: this metric, between these
     * dates. Move either and every row is still displayed, still ranked, and
     * no longer true — which nothing said, because `synced_at` answers "when
     * was this read" and not "was it read about the same question".
     */
    #[Test]
    public function moving_the_window_marks_the_standings_stale(): void
    {
        $event = $this->race();
        $host = $this->host($event);
        $this->readEntrant($event, 'Pondake');

        $this->assertNull($event->standings_stale_since);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => $event->title,
            'start_date' => Carbon::now()->subMonth()->toDateString(),
            'end_date' => Carbon::now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $this->assertNotNull($event->fresh()->standings_stale_since);
    }

    #[Test]
    public function changing_the_metric_marks_them_stale_too(): void
    {
        $event = $this->race();
        $host = $this->host($event);
        $this->readEntrant($event, 'Pondake');

        $this->actingAs($host)->patch("/events/{$event->id}", ['title' => $event->title, 'metric' => 'fishing']);

        $this->assertNotNull($event->fresh()->standings_stale_since);
    }

    /**
     * Nothing read yet is not the same as read against the old question.
     *
     * The banner says "these numbers are out of date", and a race nobody has
     * entered has no numbers. Reported from a brand-new drop race: zero
     * participants, the standings panel reading "Numbers not read yet", and
     * a warning above it that the numbers beside it were stale. Setting the
     * dates on an event you just made is the normal way to make one.
     */
    #[Test]
    public function an_event_nobody_has_entered_never_goes_stale(): void
    {
        $event = $this->race();
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => $event->title,
            'metric' => 'fishing',
            'start_date' => Carbon::now()->subMonth()->toDateString(),
            'end_date' => Carbon::now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $this->assertNull($event->fresh()->standings_stale_since);
    }

    /**
     * And an entrant who has never been synced holds no measurement either —
     * the row exists, `gained` is null, and there is still nothing on screen
     * that the old window could have produced.
     */
    #[Test]
    public function an_entrant_who_was_never_read_does_not_make_the_event_stale(): void
    {
        $event = $this->race();
        $host = $this->host($event);
        $this->entrant($event, 'Pondake');

        $this->actingAs($host)->patch("/events/{$event->id}", ['title' => $event->title, 'metric' => 'fishing']);

        $this->assertNull($event->fresh()->standings_stale_since);
    }

    /**
     * The whole reported sequence: join, the host changes the boss, leave.
     *
     * Marking the event stale was right at the time — there was a measured
     * row on screen describing the old boss. Deleting that row on the way out
     * is what makes the warning false, and nothing was re-asking the
     * question, so the banner stayed above an empty table on an event with
     * nobody in it.
     */
    #[Test]
    public function the_last_entrant_leaving_takes_the_warning_with_them(): void
    {
        $event = $this->race(['type' => 'DROP_RACE', 'metric' => 'abyssal_sire']);
        $host = $this->host($event);
        $entrant = $this->readEntrant($event, 'Pondake');

        $this->actingAs($host)->patch("/events/{$event->id}", ['title' => $event->title, 'metric' => 'zulrah']);
        $this->assertNotNull($event->fresh()->standings_stale_since);

        $this->actingAs($entrant->user)->delete("/events/{$event->id}/enter")->assertRedirect();

        $this->assertSame(0, $event->standings()->count());
        $this->assertNull($event->fresh()->standings_stale_since);
    }

    /** But somebody else's numbers still being there keeps it honest. */
    #[Test]
    public function one_of_two_entrants_leaving_keeps_the_warning(): void
    {
        $event = $this->race();
        $host = $this->host($event);
        $leaver = $this->readEntrant($event, 'Pondake');
        $this->readEntrant($event, 'Zezima');

        $this->actingAs($host)->patch("/events/{$event->id}", ['title' => $event->title, 'metric' => 'fishing']);

        $this->actingAs($leaver->user)->delete("/events/{$event->id}/enter")->assertRedirect();

        $this->assertNotNull($event->fresh()->standings_stale_since);
    }

    /** An edit that leaves the window alone leaves the numbers alone. */
    #[Test]
    public function renaming_an_event_does_not_make_its_numbers_suspect(): void
    {
        $event = $this->race();
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", ['title' => 'A better name']);

        $this->assertNull($event->fresh()->standings_stale_since);
    }

    /** And a board event has no window to move. */
    #[Test]
    public function a_board_event_never_goes_stale(): void
    {
        $event = Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $host = $this->host($event);

        $this->actingAs($host)->patch("/events/{$event->id}", [
            'title' => $event->title,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addWeek()->toDateString(),
        ]);

        $this->assertNull($event->fresh()->standings_stale_since);
    }

    #[Test]
    public function a_finished_sync_clears_the_warning(): void
    {
        Http::fake(['*wiseoldman.net*' => Http::response($this->gains(10))]);

        $event = $this->race();
        $event->forceFill(['standings_stale_since' => Carbon::now()])->save();
        $this->entrant($event, 'Pondake');

        $this->actingAs($this->host($event))->post("/events/{$event->id}/standings/sync");

        $this->assertNull($event->fresh()->standings_stale_since);
    }

    /**
     * Even when a name came back empty. Somebody Wise Old Man cannot measure
     * is not a reason to keep telling everyone the whole table is out of date.
     */
    #[Test]
    public function it_clears_even_when_one_entrant_could_not_be_measured(): void
    {
        Http::fake(['*wiseoldman.net*' => Http::response(['data' => ['skills' => []]])]);

        $event = $this->race();
        $event->forceFill(['standings_stale_since' => Carbon::now()])->save();
        $this->entrant($event, 'Not A Player');

        $this->actingAs($this->host($event))->post("/events/{$event->id}/standings/sync");

        $this->assertNull($event->fresh()->standings_stale_since);
    }

    /** The race page has to receive it, and the live channel has to push it. */
    #[Test]
    public function the_page_and_the_stream_both_carry_the_warning(): void
    {
        $event = $this->race();
        $this->entrant($event, 'Pondake');
        $channel = app(EventChannelResolver::class)->for($event);
        $before = $channel->fingerprint($event);

        $event->forceFill(['standings_stale_since' => Carbon::now()])->save();

        $this->assertNotSame($before, $channel->fingerprint($event));

        $this->actingAs($this->host($event))
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('event.standings_stale_since', fn ($value) => $value !== null));
    }

    /** Nothing to sync on an event type that does not race on a metric. */
    #[Test]
    public function a_board_event_has_no_standings_to_sync(): void
    {
        $event = Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->actingAs($this->host($event))
            ->post("/events/{$event->id}/standings/sync")
            ->assertNotFound();
    }
}
