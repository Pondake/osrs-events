<?php

namespace Tests\Feature;

use App\Models\BingoCard;
use App\Models\Board;
use App\Models\BoardAccess;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\Event;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Tile;
use App\Models\User;
use App\Services\BoardAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The answers the app gives when somebody does the ordinary thing.
 *
 * These came out of walking the app as each kind of user rather than out of a
 * bug report, which is why they are grouped by the person rather than by the
 * controller: a player who joins and cannot play, a clan whose private event
 * an admin is reading, a page that says "no players yet" about an event five
 * people are scoring in. None of them were errors. All of them told somebody
 * something untrue.
 */
class EventExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $type = 'SNAKES_LADDERS', array $attributes = []): Event
    {
        return Event::create([
            'title' => "A {$type} event",
            'type' => $type,
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
            ...$attributes,
        ]);
    }

    private function board(array $attributes = []): Event
    {
        $event = $this->event('SNAKES_LADDERS', $attributes);
        $board = Board::create(['event_id' => $event->id, 'size' => 'SIZE_5X5']);

        foreach (range(0, 24) as $position) {
            Tile::create(['board_id' => $board->id, 'position' => $position, 'type' => 'NORMAL']);
        }

        return $event->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $admin;
    }

    // ------------------------------------------------- joining a team event

    /**
     * Joining a team event you have no team in is refused outright.
     *
     * It used to succeed and answer "you are in, a host has to put you on a
     * team" — but hosts add teams to an event, not people to a team, so that
     * was advice nobody could act on, and the membership it left behind had
     * no board, no score and every control on the page refusing. The team is
     * the way in, so it comes first.
     */
    #[Test]
    public function joining_a_team_event_without_a_team_is_refused(): void
    {
        $event = $this->board(['mode' => 'TEAM']);
        $player = User::factory()->create();

        $this->actingAs($player)->post("/events/{$event->id}/join")
            ->assertSessionHas('board-save-error', trans('events.team_required_notice'));

        $this->assertDatabaseCount('event_participants', 0);
        $this->assertDatabaseCount('player_boards', 0);
    }

    #[Test]
    public function joining_a_team_event_with_a_team_says_the_ordinary_thing(): void
    {
        $event = $this->board(['mode' => 'TEAM']);
        $player = User::factory()->create();
        $team = Team::create(['name' => 'Team One']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $player->id]);
        BoardTeam::create(['event_id' => $event->id, 'team_id' => $team->id]);

        $this->actingAs($player)->post("/events/{$event->id}/join")
            ->assertSessionHas('board-save', trans('events.joined'));

        $this->assertDatabaseCount('player_boards', 1);
    }

    /**
     * An open team event had no way in at all: teams were assigned by the
     * host, so somebody who ran a team and was allowed to join could still do
     * nothing. Bringing their own team in is the way in, and it happens on
     * the same join.
     */
    #[Test]
    public function a_team_owner_can_bring_their_team_into_an_event_they_may_join(): void
    {
        $event = $this->board(['mode' => 'TEAM']);
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Team One']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($owner)->post("/events/{$event->id}/join", ['team_id' => $team->id])
            ->assertSessionHas('board-save', trans('events.joined'));

        $this->assertDatabaseHas('board_teams', ['event_id' => $event->id, 'team_id' => $team->id]);
        $this->assertDatabaseCount('player_boards', 1);
    }

    /**
     * Entering a team commits that whole team's score to an event, so it is
     * the team's own call — a plain member cannot make it for everybody else.
     */
    #[Test]
    public function a_plain_member_cannot_bring_their_team_into_an_event(): void
    {
        $event = $this->board(['mode' => 'TEAM']);
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Team One']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::MEMBER]);

        $this->actingAs($member)->post("/events/{$event->id}/join", ['team_id' => $team->id])
            ->assertSessionHas('board-save-error', trans('events.team_not_yours'));

        $this->assertDatabaseCount('board_teams', 0);
        $this->assertDatabaseCount('event_participants', 0);
    }

    #[Test]
    public function joining_a_solo_event_says_the_ordinary_thing(): void
    {
        $event = $this->board();
        $player = User::factory()->create();

        $this->actingAs($player)->post("/events/{$event->id}/join")
            ->assertSessionHas('board-save', trans('events.joined'));
    }

    // ------------------------------------------------------- the leaderboard

    /**
     * `/leaderboard` IS the Snakes & Ladders ranking — who is furthest along
     * the track. Rendered for an event with no board it said "No players yet"
     * about a bingo card five people were scoring on, which is a dead end
     * that contradicts the event page it was reached from.
     */
    #[Test]
    public function the_leaderboard_of_a_board_less_event_goes_to_the_event(): void
    {
        $event = $this->event('BINGO');
        BingoCard::create(['event_id' => $event->id, 'size' => 3]);

        $this->actingAs(User::factory()->create())
            ->get("/events/{$event->id}/leaderboard")
            ->assertRedirect("/events/{$event->id}");
    }

    #[Test]
    public function a_board_event_still_has_its_leaderboard(): void
    {
        $event = $this->board();

        $this->actingAs(User::factory()->create())
            ->get("/events/{$event->id}/leaderboard")
            ->assertOk();
    }

    // ---------------------------------------------- reading over a shoulder

    /**
     * An admin may read every private event — that is what moderating means.
     * Doing it silently is a different thing from being allowed to do it, and
     * /teams has said so out loud since it was built.
     */
    #[Test]
    public function an_admin_reading_a_private_event_is_told_they_are_doing_that(): void
    {
        $access = app(BoardAccessService::class);
        $event = $this->event('BINGO', ['access_mode' => 'INVITE']);

        $this->assertTrue($access->isAdminOnlyView($this->admin(), $event));
    }

    #[Test]
    public function nobody_else_gets_that_notice(): void
    {
        $access = app(BoardAccessService::class);
        $admin = $this->admin();

        // An open event is open. Nothing is being overridden.
        $this->assertFalse($access->isAdminOnlyView($admin, $this->event()));

        // An admin who runs the event is here as its host.
        $hosted = $this->event('BINGO', ['access_mode' => 'INVITE']);
        BoardAuthor::create(['event_id' => $hosted->id, 'user_id' => $admin->id, 'is_owner' => true]);
        $this->assertFalse($access->isAdminOnlyView($admin, $hosted));

        // An admin who was actually invited came in the front door.
        $invited = $this->event('BINGO', ['access_mode' => 'INVITE']);
        BoardAccess::create(['id' => (string) str()->uuid(), 'event_id' => $invited->id, 'user_id' => $admin->id, 'access_mode' => 'INVITE']);
        $this->assertFalse($access->isAdminOnlyView($admin, $invited));

        // And an ordinary player never sees it, because they never get in.
        $this->assertFalse($access->isAdminOnlyView(User::factory()->create(), $this->event('BINGO', ['access_mode' => 'INVITE'])));
    }

    #[Test]
    public function the_event_page_carries_the_notice(): void
    {
        $event = $this->board(['access_mode' => 'INVITE']);

        $this->actingAs($this->admin())
            ->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('viewingAsAdmin', true));
    }

    // ------------------------------------------------------- the webhook URL

    /**
     * A webhook URL is a capability: anyone holding it can post into that
     * Discord channel. It must never travel in the event payload, which every
     * viewer of a public event receives and the live channel re-pushes every
     * few seconds.
     */
    #[Test]
    public function the_webhook_url_reaches_editors_and_nobody_else(): void
    {
        $event = $this->board();
        $event->update(['discord_webhook_url' => 'https://discord.com/api/webhooks/1/secret']);
        $host = User::factory()->create();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $host->id, 'is_owner' => true]);

        $this->actingAs($host)->get("/events/{$event->id}")
            ->assertInertia(fn ($page) => $page->where('webhookUrl', 'https://discord.com/api/webhooks/1/secret'));

        $response = $this->actingAs(User::factory()->create())->get("/events/{$event->id}");

        $response->assertInertia(fn ($page) => $page->where('webhookUrl', null));
        $response->assertDontSee('secret');
    }
}
