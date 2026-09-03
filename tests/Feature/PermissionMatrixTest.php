<?php

namespace Tests\Feature;

use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Role;
use App\Models\User;
use App\Services\BingoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who may do what, written down once instead of six times.
 *
 * The rules are spread across three places by design — route middleware, the
 * two assertions in Controller, and `User::hasPermission()` — and each is
 * tested where it lives. What none of those cover is the SHAPE: that an admin
 * is an ordinary user on the public side, that a co-host may run an event but
 * not end it, that "can create events" is a permission rather than a role.
 * Those are the rules somebody breaks by accident while fixing something else,
 * because each individual change looks reasonable.
 *
 * Read it as a table. A row that moves should move on purpose.
 */
class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $owner;

    private Team $team;

    private User $teamOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->player('Owner');
        $this->event = Event::create([
            'title' => 'Clan night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        $this->event->bingoCard()->create(['size' => 3]);
        BoardAuthor::create(['event_id' => $this->event->id, 'user_id' => $this->owner->id, 'is_owner' => true]);
    }

    private function player(string $name): User
    {
        return User::factory()->create(['osrs_username' => $name.' '.fake()->unique()->numberBetween(100, 9999)]);
    }

    private function coHost(): User
    {
        $user = $this->player('CoHost');
        BoardAuthor::create(['event_id' => $this->event->id, 'user_id' => $user->id, 'is_owner' => false]);

        return $user;
    }

    private function creator(): User
    {
        $user = $this->player('Creator');
        $user->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));

        return $user;
    }

    private function admin(): User
    {
        $user = $this->player('Admin');
        $user->assignRole(Role::findOrCreate('ADMIN', 'web'));

        return $user;
    }

    /** @return array<string, User|null> */
    private function everyone(): array
    {
        return [
            'guest' => null,
            'player' => $this->player('Player'),
            'creator' => $this->creator(),
            'co-host' => $this->coHost(),
            'owner' => $this->owner,
            'admin' => $this->admin(),
        ];
    }

    /**
     * @param  array<string, int|string>  $expected  status per persona, or
     *                                               'redirect' for any 3xx
     * @param  array<string, User|null>|null  $seats  the ladder to walk, when
     *                                                it is not the event one —
     *                                                team rights are per team,
     *                                                so they have their own
     */
    private function assertMatrix(string $method, string $url, array $expected, array $payload = [], ?array $seats = null): void
    {
        foreach ($seats ?? $this->everyone() as $persona => $user) {
            if (! array_key_exists($persona, $expected)) {
                continue;
            }

            // Cleared between personas on purpose: actingAs() sets the guard
            // for the REST of the test, so a "guest" row after an
            // authenticated one is only a guest if the guard is put back.
            // Without this the guest column quietly tests whoever ran last —
            // which is how a matrix test grows a hole in the one column it
            // exists to watch.
            $this->app['auth']->forgetGuards();

            $request = $user === null ? $this : $this->actingAs($user);
            $response = $request->call($method, $url, $payload);

            $want = $expected[$persona];
            $got = $response->getStatusCode();

            if ($want === 'redirect') {
                $this->assertTrue($got >= 300 && $got < 400, "{$persona} {$method} {$url}: expected a redirect, got {$got}");

                continue;
            }

            $this->assertSame($want, $got, "{$persona} {$method} {$url}");
        }
    }

    // ------------------------------------------------------------ reading

    /** The public list is the page search engines index, so it needs no login. */
    #[Test]
    public function the_events_list_is_open_to_everyone(): void
    {
        $this->assertMatrix('GET', '/events', [
            'guest' => 200, 'player' => 200, 'admin' => 200,
        ]);
    }

    /**
     * A listed event opens for everyone, signed in or not — changed
     * 2026-08-31. `/events` had always advertised these to strangers with
     * clickable cards, and every one of those clicks hit a login redirect.
     */
    #[Test]
    public function a_listed_event_page_is_open_to_everyone(): void
    {
        $this->assertMatrix('GET', "/events/{$this->event->id}", [
            'guest' => 200, 'player' => 200, 'co-host' => 200, 'owner' => 200, 'admin' => 200,
        ]);
    }

    /** An unlisted one still asks who you are. Nothing about it was public. */
    #[Test]
    public function an_unlisted_event_page_still_needs_a_login(): void
    {
        $this->event->update(['is_listed' => false]);

        $this->assertMatrix('GET', "/events/{$this->event->id}", [
            'guest' => 'redirect', 'owner' => 200, 'admin' => 200,
        ]);
    }

    /**
     * A private event renders the gate rather than a 403 — the difference
     * between "no" and "not like this", since the gate is where an invite
     * code is typed. An admin reads the event itself; the page tells them
     * that is why (see EventExperienceTest).
     */
    #[Test]
    public function a_private_event_shows_the_gate_to_a_stranger(): void
    {
        // Unlisted as well as invite-only: since 2026-08-31 a LISTED
        // invite-only event is readable by anyone, with the roster withheld
        // rather than the page — the gate is what an unadvertised event
        // shows.
        $this->event->update(['access_mode' => 'INVITE', 'is_listed' => false]);

        $this->actingAs($this->player('Stranger'))
            ->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Boards/AccessGate'));

        $this->actingAs($this->admin())
            ->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page->component('Events/Bingo')->where('viewingAsAdmin', true));
    }

    // ------------------------------------------------------------ creating

    /** Creating is a permission, not a role — an admin has it by bypass. */
    #[Test]
    public function creating_an_event_takes_the_permission(): void
    {
        $payload = [
            'title' => 'A new one',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ];

        $this->assertMatrix('POST', '/events', [
            'player' => 403, 'creator' => 'redirect', 'admin' => 'redirect',
        ], $payload);
    }

    // ------------------------------------------------------------- running

    /**
     * The line this whole split exists for: editing is for whoever hosts the
     * event, and an admin out here is an ordinary user. The admin route is
     * how they reach for the power, and it is a different URL on purpose.
     */
    #[Test]
    public function editing_an_event_is_for_its_hosts_and_an_admin_uses_the_admin_route(): void
    {
        $this->assertMatrix('PATCH', "/events/{$this->event->id}", [
            'player' => 403, 'co-host' => 'redirect', 'owner' => 'redirect', 'admin' => 403,
        ], ['title' => 'Renamed']);

        $this->assertMatrix('PATCH', "/admin/events/{$this->event->id}", [
            'player' => 403, 'co-host' => 403, 'owner' => 403, 'admin' => 'redirect',
        ], ['title' => 'Renamed by an admin']);
    }

    #[Test]
    public function pausing_is_a_host_action_and_deleting_is_the_owners(): void
    {
        $this->assertMatrix('PATCH', "/events/{$this->event->id}/pause", [
            'player' => 403, 'co-host' => 'redirect', 'admin' => 403,
        ], ['paused' => true, 'notify' => false]);

        // Resumed so the delete below is not acting on a paused event.
        $this->event->forceFill(['paused_at' => null])->save();

        $this->assertMatrix('DELETE', "/events/{$this->event->id}", [
            'player' => 403, 'co-host' => 403,
        ], ['notify' => false]);

        $this->actingAs($this->owner)->delete("/events/{$this->event->id}", ['notify' => false])->assertRedirect();
    }

    /** Handing out links is running an event — the reported 403 for co-hosts. */
    #[Test]
    public function invite_links_follow_the_host_line_not_the_owner_line(): void
    {
        $this->assertMatrix('GET', "/events/{$this->event->id}/invites", [
            'player' => 403, 'co-host' => 200, 'owner' => 200,
        ]);

        $this->assertMatrix('POST', "/events/{$this->event->id}/invites", [
            'player' => 403, 'co-host' => 200,
        ]);
    }

    // -------------------------------------------------------------- taking part

    #[Test]
    public function joining_is_open_to_any_signed_in_player(): void
    {
        $this->assertMatrix('POST', "/events/{$this->event->id}/join", [
            'guest' => 'redirect', 'player' => 'redirect', 'admin' => 'redirect',
        ]);
    }

    // ------------------------------------------------------------------ teams

    /**
     * Team rights are per team, so they need their own ladder.
     *
     * The extra seat is the point: **ex-global-manager** holds `TEAM_MANAGER`,
     * the site-wide role retired on 2026-08-24, and is not in the team. It used
     * to manage every team on the site. Carrying it as a seat rather than as
     * one test means every row below asserts it grants nothing, and a row that
     * ever came back green would say so out loud.
     *
     * @return array<string, User|null>
     */
    private function teamSeats(): array
    {
        return [
            'guest' => null,
            'outsider' => $this->player('Outsider'),
            'ex-global-manager' => tap($this->player('ExGlobal'), fn (User $u) => $u->assignRole(Role::findOrCreate('TEAM_MANAGER', 'web'))),
            'member' => $this->teamMember(TeamMember::MEMBER),
            'manager' => $this->teamMember(TeamMember::MANAGER),
            'owner' => $this->teamOwner,
            'admin' => $this->admin(),
        ];
    }

    private function teamMember(string $role): User
    {
        $user = $this->player($role);

        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    /** Created lazily: most rows in this file have no team in them. */
    private function withTeam(): void
    {
        $this->teamOwner = $this->player('TeamOwner');
        $this->team = Team::create(['name' => 'Zulrah Enjoyers']);

        TeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $this->teamOwner->id,
            'role' => TeamMember::OWNER,
        ]);
    }

    #[Test]
    public function renaming_a_team_is_for_the_people_running_it(): void
    {
        $this->withTeam();

        $this->assertMatrix('PATCH', "/teams/{$this->team->id}", [
            'guest' => 'redirect',
            'outsider' => 403,
            'ex-global-manager' => 403,
            'member' => 403,
            'manager' => 'redirect',
            'owner' => 'redirect',
            'admin' => 'redirect',
        ], ['name' => 'Renamed'], $this->teamSeats());
    }

    #[Test]
    public function adding_someone_to_a_team_is_for_the_people_running_it(): void
    {
        $this->withTeam();
        $recruit = $this->player('Recruit');

        $this->assertMatrix('POST', "/teams/{$this->team->id}/members", [
            'outsider' => 403,
            'ex-global-manager' => 403,
            'member' => 403,
            'manager' => 'redirect',
        ], ['user_id' => $recruit->id], $this->teamSeats());
    }

    /**
     * Promotion is the owner's alone, so a manager cannot quietly make more
     * managers — the one place the manager seat stops short of running things.
     */
    #[Test]
    public function promoting_a_member_is_the_owners_alone(): void
    {
        $this->withTeam();
        $seats = $this->teamSeats();
        $target = $seats['member'];

        $this->assertMatrix('PATCH', "/teams/{$this->team->id}/members/{$target->id}", [
            'outsider' => 403,
            'ex-global-manager' => 403,
            'member' => 403,
            'manager' => 403,
            'owner' => 'redirect',
        ], ['role' => TeamMember::MANAGER], $seats);
    }

    /**
     * Deleting takes the whole team's history with it, so it stops at the
     * owner. Asserted as refusals first and the one success last, because a
     * successful delete would leave every later row testing a 404.
     */
    #[Test]
    public function deleting_a_team_is_the_owners_alone(): void
    {
        $this->withTeam();

        $this->assertMatrix('DELETE', "/teams/{$this->team->id}", [
            'outsider' => 403,
            'ex-global-manager' => 403,
            'member' => 403,
            'manager' => 403,
        ], [], $this->teamSeats());

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->teamOwner)->delete("/teams/{$this->team->id}")->assertRedirect();
    }

    /**
     * The owner cannot be removed or demoted out of their own team — either
     * would leave it with nobody who can delete it and no way to appoint one.
     */
    #[Test]
    public function a_team_always_keeps_someone_who_can_end_it(): void
    {
        $this->withTeam();

        $this->actingAs($this->teamOwner)
            ->delete("/teams/{$this->team->id}/members/{$this->teamOwner->id}")
            ->assertForbidden();

        $this->actingAs($this->teamOwner)
            ->patch("/teams/{$this->team->id}/members/{$this->teamOwner->id}", ['role' => TeamMember::MEMBER])
            ->assertForbidden();
    }

    // ------------------------------------------------------------- the admin area

    #[Test]
    public function the_admin_area_is_shut_to_everyone_else(): void
    {
        $this->assertMatrix('GET', '/admin', [
            'guest' => 'redirect', 'player' => 403, 'creator' => 403, 'owner' => 403, 'admin' => 200,
        ]);

        $this->assertMatrix('GET', '/admin/events', [
            'player' => 403, 'admin' => 200,
        ]);

        $this->assertMatrix('GET', '/admin/audit', [
            'player' => 403, 'admin' => 200,
        ]);
    }

    /**
     * Except the two pages that are not about being an admin at all. Tasks
     * needs `canCreateTiles` and blueprints needs `canCreateBoards`, which is
     * why the sidebar filters per item rather than hiding everything behind
     * one role.
     */
    #[Test]
    public function an_editor_reaches_the_two_pages_their_permission_is_for(): void
    {
        $editor = $this->player('Editor');
        $editor->givePermissionTo(Permission::findOrCreate('canCreateTiles', 'web'));

        $this->actingAs($editor)->get('/admin/tasks')->assertOk();
        $this->actingAs($editor)->get('/admin')->assertForbidden();

        $this->actingAs($this->creator())->get('/admin/blueprints')->assertOk();
    }

    // ------------------------------------------------------------- own settings

    #[Test]
    public function your_own_settings_need_nothing_but_a_login(): void
    {
        foreach (['/settings/profile', '/settings/account', '/teams', '/my-events'] as $url) {
            $this->assertMatrix('GET', $url, ['guest' => 'redirect', 'player' => 200]);
        }
    }

    // ------------------------------------- progress is public, names are not

    /**
     * The rule set 2026-08-31, in the owner's own words: a public event may
     * be read and its player progression seen, but if the access mode is not
     * OPEN the players are anonymous — progress yes, roster no.
     *
     * Listing an event advertises that it exists. It does not publish the
     * clan's roster, and a host who set INVITE said so twice.
     */
    #[Test]
    public function a_listed_invite_only_event_shows_progress_without_names(): void
    {
        $this->event->update(['access_mode' => 'INVITE']);

        // A real approved claim, because "every row is anonymous" is
        // vacuously true of an empty table — the first version of this test
        // passed without a single standings row in it.
        $this->approvedClaim();

        $this->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // Every standings row keeps its score and loses its identity.
                ->where('standings', fn ($rows) => collect($rows)->every(
                    fn ($row) => $row['name'] === null && $row['avatarUrl'] === null && $row['id'] === null,
                )));
    }

    /**
     * The same rule on a metric race, which carries a second name.
     *
     * A race's standings row has `name` (the RSN) and `displayName` (the
     * Discord one, printed underneath it). The anonymiser was written for
     * bingo's row shape, which has only the first, so a listed invite-only
     * drop race rendered "Anonymous player" in italics with the player's
     * real name directly below it — reported from a screenshot of exactly
     * that. Every identity field, or the label is theatre.
     */
    #[Test]
    public function a_listed_invite_only_race_hides_both_of_its_names(): void
    {
        $race = Event::create([
            'title' => 'Boss of the month',
            'type' => 'DROP_RACE',
            'metric' => 'abyssal_sire',
            'mode' => 'SOLO',
            'access_mode' => 'INVITE',
            'is_listed' => true,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeek(),
        ]);
        $entrant = $this->player('Racer');
        $entrant->update(['nickname' => 'Marthijn']);
        \App\Models\EventStanding::create([
            'event_id' => $race->id,
            'user_id' => $entrant->id,
            'username' => $entrant->osrs_username,
            'gained' => 12,
            'synced_at' => now(),
        ]);

        $this->get("/events/{$race->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('standings', fn ($rows) => collect($rows)->isNotEmpty() && collect($rows)->every(
                    fn ($row) => $row['name'] === null
                        && $row['displayName'] === null
                        && $row['avatarUrl'] === null
                        && $row['id'] === null,
                ))
                // The score itself stays — that is the half that is public.
                ->where('standings.0.gained', 12));
    }

    /**
     * And the ranking page, which had no rule at all.
     *
     * `/leaderboard` gates on canView and then published every player's
     * nickname, Discord username and avatar to whoever got in — on a listed
     * invite-only event, the clan roster, on the one page whose whole
     * subject is the roster. The board itself had been anonymising since
     * 2026-08-31; this was the same event's other page.
     */
    #[Test]
    public function a_listed_invite_only_boards_ranking_hides_its_roster(): void
    {
        $board = Event::create([
            'title' => 'Clan race',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'INVITE',
            'is_listed' => true,
        ]);
        $game = \App\Models\Board::create(['event_id' => $board->id, 'size' => 'SIZE_5X5']);
        $player = $this->player('Runner');
        $player->update(['nickname' => 'Marthijn']);
        \App\Models\PlayerBoard::create([
            'user_id' => $player->id,
            'board_id' => $game->id,
            'current_position' => 7,
        ]);

        $this->get("/events/{$board->id}/leaderboard")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('namesArePublic', false)
                // Progress kept, identity gone — the same trade the board makes.
                ->where('entries.0.currentPosition', 7)
                ->where('entries.0.user', null)
                ->where('entries.0.team', null));
    }

    /** On an OPEN event the same reader sees who is playing. */
    #[Test]
    public function a_listed_open_event_shows_who_is_playing(): void
    {
        $claimant = $this->approvedClaim();

        $this->get("/events/{$this->event->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('standings', fn ($rows) => collect($rows)->contains(
                    fn ($row) => $row['name'] === $claimant->displayName(),
                )));
    }

    /** One approved square, so the standings table has something in it. */
    private function approvedClaim(): User
    {
        $claimant = $this->player('Claimant');

        // The card is created bare in setUp; the controller fills it in on
        // render, which is too late for a claim made here.
        app(BingoService::class)->ensureSquares($this->event->bingoCard);

        $square = $this->event->bingoCard->squares()->orderBy('position')->first();

        BingoCompletion::create([
            'bingo_square_id' => $square->id,
            'user_id' => $claimant->id,
            'marked_by' => $claimant->id,
            'status' => 'APPROVED',
        ]);

        return $claimant;
    }

    /**
     * The way into a listed invite-only event, on the event page itself.
     *
     * Boards/AccessGate used to hold the code field, and a listed invite-only
     * event no longer renders it (see canView) — so without this the only
     * route in was an invite LINK, and anyone holding a bare code had nowhere
     * to type it.
     */
    #[Test]
    public function a_listed_invite_only_event_offers_a_code_field_to_outsiders(): void
    {
        $this->event->update(['access_mode' => 'INVITE']);

        // Signed out: the card appears and tells them to sign in first, since
        // the join endpoint is behind auth.
        $this->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page->where('needsInvite', true));

        // Signed in, not in the event: same card, with the field.
        $this->actingAs($this->player('Stranger'))
            ->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page->where('needsInvite', true));
    }

    /** Nobody who is already in it should be asked for a code. */
    #[Test]
    public function the_code_field_is_not_shown_to_people_already_in_the_event(): void
    {
        $this->event->update(['access_mode' => 'INVITE']);

        $this->actingAs($this->owner)
            ->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page->where('needsInvite', false));
    }

    /** And an OPEN event never asks — there is nothing to hand in. */
    #[Test]
    public function an_open_event_never_asks_for_a_code(): void
    {
        $this->get("/events/{$this->event->id}")
            ->assertInertia(fn ($page) => $page->where('needsInvite', false));
    }

    /**
     * The one gate that stands in front of everything else: an account with
     * no OSRS name is sent to the page that asks for one, whatever it was
     * trying to open.
     */
    #[Test]
    public function an_account_without_an_osrs_name_is_sent_to_the_page_that_asks(): void
    {
        // While the first-run wizard is still open the gate stays out of the
        // way — the wizard asks for the same field, and two demands for one
        // answer is the version of this that shipped once already.
        $midWizard = User::factory()->create(['osrs_username' => null, 'onboarding_completed_at' => null]);
        $this->actingAs($midWizard)->get('/my-events')->assertOk();

        $newcomer = User::factory()->create(['osrs_username' => null, 'onboarding_completed_at' => now()]);

        $this->actingAs($newcomer)->get('/my-events')->assertRedirect('/welcome/osrs-username');

        // Reading an event is the exception, since 2026-08-31: the gate
        // exists to stop somebody PLAYING without a name to score, and a
        // listed event is readable by strangers with no account at all — so
        // demanding one from a signed-in reader made no sense.
        $this->actingAs($newcomer)->get("/events/{$this->event->id}")->assertOk();

        // And is not locked inside it: the page that asks is reachable, and
        // so is the way out. `from()` gives the POST a session to invalidate,
        // which is what signing out actually does here.
        $this->actingAs($newcomer)->get('/welcome/osrs-username')->assertOk();
        $this->actingAs($newcomer)->from('/welcome/osrs-username')->post('/logout')->assertRedirect('/');
    }
}
