<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStanding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Entering, leaving, and the live channel.
 *
 * Entering is deliberately its own action rather than something derived from
 * access: an OPEN event grants access *without storing a row*, so a
 * leaderboard built from access records would be empty for the commonest
 * mode — and where it did work, it would enrol anyone who merely looked at a
 * public leaderboard.
 */
class SkillRaceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function race(array $attributes = []): Event
    {
        return Event::create([
            'title' => 'Skill of the Month — Mining',
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

    private function player(string $name = 'Pondake'): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    #[Test]
    public function entering_adds_the_user_to_the_standings(): void
    {
        $event = $this->race();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/enter")->assertRedirect();

        $this->assertSame('Pondake', EventStanding::firstOrFail()->username);
    }

    /**
     * Entering used to leave the row blank until the scheduled command got
     * to it, so the first thing anyone saw after joining a race was "waiting
     * for first sync" for up to ten minutes — indistinguishable from broken.
     *
     * Done inline rather than queued because this app runs no queue worker;
     * a dispatched job would sit in the table forever. The enter route is
     * throttled, which is what makes an outbound call there affordable.
     */
    #[Test]
    public function entering_baselines_the_standing_immediately(): void
    {
        Http::fake(['api.wiseoldman.net/v2/players/*/gained*' => Http::response([
            'data' => ['skills' => ['mining' => ['metric' => 'mining', 'experience' => [
                'gained' => 1000,
                'start' => 500000,
                'end' => 501000,
            ]]]],
        ])]);

        $event = $this->race();

        $this->actingAs($this->player())->post("/events/{$event->id}/enter")->assertRedirect();

        $standing = EventStanding::firstOrFail();

        $this->assertNotNull($standing->synced_at, 'the row should be synced on the way in');
        $this->assertSame(1000, $standing->gained);
        $this->assertNull($standing->sync_error);
    }

    /**
     * The lookup is somebody else's API and will fail sometimes. Losing the
     * entry over that would be the worse outcome by far — the scheduled sync
     * retries, and the row carries its own error in the meantime.
     */
    #[Test]
    public function a_failing_lookup_does_not_cost_the_entry(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response('', 500)]);

        $event = $this->race();

        $this->actingAs($this->player())->post("/events/{$event->id}/enter")->assertRedirect();

        $this->assertSame(1, EventStanding::count());
    }

    /** Looking at a public leaderboard must not enrol anyone. */
    #[Test]
    public function merely_viewing_an_open_race_does_not_enter_it(): void
    {
        $event = $this->race();

        $this->actingAs($this->player())->get("/events/{$event->id}")->assertOk();

        $this->assertSame(0, EventStanding::count());
    }

    #[Test]
    public function leaving_removes_the_standing(): void
    {
        $event = $this->race();
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/enter");

        $this->actingAs($user)->delete("/events/{$event->id}/enter")->assertRedirect();

        $this->assertSame(0, EventStanding::count());
    }

    #[Test]
    public function entering_without_an_osrs_name_is_refused_with_a_message(): void
    {
        $event = $this->race();
        // Past the gate middleware via a name, then cleared — the state an
        // admin wipe or a future migration could produce.
        $user = $this->player();
        $user->forceFill(['osrs_username' => null])->save();

        $this->actingAs($user)->post("/events/{$event->id}/enter")->assertRedirect('/welcome/osrs-username');

        $this->assertSame(0, EventStanding::count());
    }

    #[Test]
    public function a_second_account_cannot_enter_under_a_name_already_racing(): void
    {
        $event = $this->race();
        $this->actingAs($this->player('Pondake'))->post("/events/{$event->id}/enter");

        $this->actingAs($this->player('Pondake'))
            ->post("/events/{$event->id}/enter")
            ->assertSessionHas('board-save-error');

        $this->assertSame(1, EventStanding::count());
    }

    #[Test]
    public function entering_a_snakes_and_ladders_event_is_not_a_thing(): void
    {
        $board = Event::create([
            'title' => 'Winter Clan Grind',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $this->actingAs($this->player())->post("/events/{$board->id}/enter")->assertNotFound();
    }

    // ------------------------------------------------------------- stream

    /**
     * Every type has a channel now, so a board streams too — it pushes player
     * positions rather than standings.
     */
    #[Test]
    public function a_board_event_streams_its_own_channel(): void
    {
        $board = Event::create([
            'title' => 'Winter Clan Grind',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);

        $response = $this->actingAs($this->player())->get("/events/{$board->id}/stream");

        $response->assertOk();
        // Laravel appends "; charset=utf-8", so the prefix is what to assert.
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));
        // nginx buffers proxied responses by default, which would hold every
        // event until the connection closed.
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }

    #[Test]
    public function the_stream_is_refused_to_someone_without_access(): void
    {
        $event = $this->race(['access_mode' => 'INVITE']);

        $this->actingAs($this->player())
            ->get("/events/{$event->id}/stream")
            ->assertForbidden();
    }

    /** Same rule as the page: an OPEN event's live channel is public too. */
    #[Test]
    public function the_stream_is_open_on_an_open_race_without_a_login(): void
    {
        $event = $this->race();

        $this->get("/events/{$event->id}/stream")->assertOk();
    }

    // --------------------------------------------------------------- page

    #[Test]
    public function the_race_page_renders_its_own_component_not_the_board_one(): void
    {
        Http::fake();
        $event = $this->race();

        $this->actingAs($this->player())
            ->get("/events/{$event->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/SkillRace')
                ->where('event.metric', 'mining')
                ->has('standings'));
    }

    // ---------------------------------------------------------- my events

    /**
     * That page was built from PlayerBoard rows, and a race has no board — so
     * entering one and then not finding it under "my events" was the result.
     */
    #[Test]
    public function a_race_you_entered_appears_under_my_events(): void
    {
        $event = $this->race();
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/enter");

        $this->actingAs($user)
            ->get('/my-events')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('boards', 1)
                ->where('boards.0.kind', 'race')
                ->where('boards.0.board.title', $event->title)
                ->has('boards.0.standing'));
    }

    #[Test]
    public function a_race_you_have_not_entered_stays_out_of_my_events(): void
    {
        $this->race();

        $this->actingAs($this->player())
            ->get('/my-events')
            ->assertInertia(fn ($page) => $page->has('boards', 0));
    }

    /** A race row carries no board preview, because there is no board. */
    #[Test]
    public function a_race_row_reports_its_placing_rather_than_progress(): void
    {
        $event = $this->race();
        $user = $this->player();
        $this->actingAs($user)->post("/events/{$event->id}/enter");

        $this->actingAs($user)
            ->get('/my-events')
            ->assertInertia(fn ($page) => $page
                ->where('boards.0.standing.participants', 1)
                ->where('boards.0.standing.gained', 0)
                ->missing('boards.0.preview')
                // Present but null, not absent: every row carries the same
                // keys so the page can branch on the value rather than on
                // which event type happened to fill them in.
                ->where('boards.0.progress', null));
    }

    #[Test]
    public function the_race_page_reports_whether_the_viewer_is_in_it(): void
    {
        $event = $this->race();
        $user = $this->player();

        $this->actingAs($user)
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('isParticipant', false));

        $this->actingAs($user)->post("/events/{$event->id}/enter");

        $this->actingAs($user)
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('isParticipant', true));
    }
}
