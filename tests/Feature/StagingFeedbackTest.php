<?php

namespace Tests\Feature;

use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\Event;
use App\Models\EventBlueprint;
use App\Models\EventStanding;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserGuild;
use App\Services\BingoService;
use App\Services\EventStandingsService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regressions for things found by using the deployed site, which the suite
 * had no reason to cover until somebody hit them.
 *
 * Each of these was reported from staging rather than caught here, and each
 * failed in a way that looked like nothing: a dropdown that was simply
 * empty, a button that could be held down, an OAuth scope that was never
 * granted, a date window that silently kept using the old dates. The point
 * of writing them down is that none of them announce themselves.
 */
class StagingFeedbackTest extends TestCase
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
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            ...$attributes,
        ]);
    }

    private function player(string $name = 'Pondake'): User
    {
        return User::factory()->create(['osrs_username' => $name]);
    }

    // ------------------------------------------------- Discord OAuth scopes

    /**
     * The driver defaults to prompt=none, which makes Discord silently reuse
     * an existing authorisation. An account that first logged in before
     * `guilds` was requested therefore got a token without it —
     * /users/@me/guilds 401s, the guild sync is non-fatal by design, and that
     * account is left with no servers forever and nothing explaining why.
     *
     * This test earned its place immediately: the first fix passed
     * ->with(['prompt' => 'consent']), which reads correctly and does
     * nothing, because getCodeFields() overwrites prompt AFTER merging
     * custom parameters. Only the driver's own withConsent() works.
     */
    #[Test]
    public function the_discord_redirect_asks_for_consent_so_new_scopes_are_granted(): void
    {
        config()->set('services.discord', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/discord/callback',
        ]);

        $location = $this->get('/auth/discord/redirect')->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);

        // The contract is that consent is not SUPPRESSED. withConsent() drops
        // the parameter rather than setting it to "consent", and Discord's
        // own default is to ask — so absent is the pass, and prompt=none is
        // the failure. Asserted on the value rather than on the absence of
        // the key, because ->with(['prompt' => 'consent']) also produces a
        // URL that looks plausible and is silently discarded by the driver.
        $this->assertNotSame('none', $query['prompt'] ?? null, 'the consent screen is being suppressed');
        $this->assertSame('identify guilds', $query['scope'] ?? null);
    }

    // ------------------------------------------------------- guild picker

    /**
     * The required-server field was a text box wanting an 18-digit snowflake.
     * It is a picker now, which only works if the guilds are actually
     * reachable — and the empty case has to be distinguishable from a
     * broken one, so the endpoint answers with a list either way.
     */
    #[Test]
    public function the_guild_picker_is_fed_this_users_discord_servers(): void
    {
        $user = $this->player();

        UserGuild::create([
            'user_id' => $user->id,
            'guild_id' => '123456789012345678',
            'guild_name' => 'Zulrah Enjoyers',
            'guild_icon' => 'abc123',
        ]);

        $this->actingAs($user)
            ->getJson('/my-guilds')
            ->assertOk()
            ->assertJsonPath('guilds.0.id', '123456789012345678')
            ->assertJsonPath('guilds.0.name', 'Zulrah Enjoyers');
    }

    #[Test]
    public function an_account_with_no_discord_gets_an_empty_guild_list_not_an_error(): void
    {
        $this->actingAs($this->player())
            ->getJson('/my-guilds')
            ->assertOk()
            ->assertJsonPath('guilds', []);
    }

    /** One person's servers must never appear in someone else's picker. */
    #[Test]
    public function the_guild_list_is_scoped_to_the_signed_in_account(): void
    {
        $mine = $this->player();
        $theirs = $this->player('Zezima');

        UserGuild::create([
            'user_id' => $theirs->id,
            'guild_id' => '999',
            'guild_name' => 'Not Yours',
        ]);

        $this->actingAs($mine)->getJson('/my-guilds')->assertOk()->assertJsonPath('guilds', []);
    }

    // ---------------------------------------------------- participation cap

    /**
     * Enter and leave are a pure toggle, so they are the easiest thing on the
     * site to hold down — and each one changes what the event's live channel
     * fingerprints, so one person spamming it makes the server push a payload
     * to every browser watching that event.
     */
    #[Test]
    public function entering_over_and_over_is_eventually_refused(): void
    {
        Http::fake();

        $event = $this->race();
        $user = $this->player();

        // The limit is 10/min; the eleventh is the one that must not land.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->post("/events/{$event->id}/enter")->assertRedirect();
        }

        $this->actingAs($user)->post("/events/{$event->id}/enter")->assertStatus(429);
    }

    #[Test]
    public function leaving_over_and_over_is_eventually_refused(): void
    {
        Http::fake();

        $event = $this->race();
        $user = $this->player();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->delete("/events/{$event->id}/enter")->assertRedirect();
        }

        $this->actingAs($user)->delete("/events/{$event->id}/enter")->assertStatus(429);
    }

    /**
     * Per user, not per route — otherwise one busy player would lock everyone
     * else out of an event they are all watching.
     */
    #[Test]
    public function one_players_spam_does_not_lock_another_player_out(): void
    {
        Http::fake();

        $event = $this->race();
        $spammer = $this->player('Spammer');
        $bystander = $this->player('Bystander');

        for ($i = 0; $i < 11; $i++) {
            $this->actingAs($spammer)->post("/events/{$event->id}/enter");
        }

        $this->actingAs($bystander)->post("/events/{$event->id}/enter")->assertRedirect();
    }

    // -------------------------------------------------------- date changes

    /**
     * Changing a race's dates has to re-track from the new window. Nothing
     * caches a baseline — refresh() rebuilds the window from the event's
     * current dates every run — but that is a property worth pinning, since
     * the alternative (a stored start value) is the obvious implementation
     * and would silently keep scoring the old window.
     */
    #[Test]
    public function moving_the_dates_moves_the_window_the_gains_are_read_over(): void
    {
        Http::fake(['api.wiseoldman.net/*' => Http::response([
            'data' => ['skills' => ['mining' => ['metric' => 'mining', 'experience' => [
                'gained' => 5, 'start' => 1, 'end' => 6,
            ]]]],
        ])]);

        $event = $this->race();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/enter")->assertRedirect();

        // Moved BACKWARDS, deliberately. refresh() declines to ask about a
        // window that has not begun — correctly, since their API answers a
        // future range with whatever the latest snapshot happens to be — so
        // a forward move would prove nothing but that early return.
        $event->forceFill([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ])->save();

        app(EventStandingsService::class)
            ->refresh($event->fresh(), EventStanding::firstOrFail());

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/gained')) {
                return false;
            }

            // The new window, not the one the row was created under.
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return str_starts_with($query['startDate'] ?? '', '2026-07-01')
                && str_starts_with($query['endDate'] ?? '', '2026-07-31');
        });
    }

    // ------------------------------------------------------ "mine" lists

    /**
     * "Mine" meant a PlayerBoard row, which only Snakes & Ladders creates.
     * So a race you entered, a bingo you claimed on, and an event you created
     * yourself were all missing from your own lists — the hub read as a bare
     * public directory and the profile said you had no boards, which is what
     * "I made a skill event but it is not even linked to me" was showing.
     */
    #[Test]
    public function a_race_you_entered_appears_under_your_events_on_the_hub(): void
    {
        Http::fake();

        $event = $this->race();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/enter")->assertRedirect();

        $props = $this->actingAs($user)->get('/events')->viewData('page')['props'];

        $this->assertSame(1, $props['playingTotal']);
        $this->assertSame($event->id, $props['playing'][0]['id']);
    }

    /** An event you run is yours whether or not you also play in it. */
    #[Test]
    public function an_event_you_created_is_yours_even_without_taking_part(): void
    {
        $event = $this->race();
        $owner = $this->player('Owner');

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $props = $this->actingAs($owner)->get('/events')->viewData('page')['props'];

        // Under "events you run", and deliberately NOT under "events you are
        // in" — hosting a race you have not entered is the normal case, and
        // the same card in both rows reads as a bug.
        $this->assertSame($event->id, $props['hosted'][0]['id'] ?? null);
        $this->assertSame([], $props['playing']);
        $this->assertSame(0, EventStanding::count(), 'the host was not entered as a participant');
    }

    #[Test]
    public function someone_elses_event_is_not_listed_as_yours(): void
    {
        $event = $this->race();
        $owner = $this->player('Owner');
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $props = $this->actingAs($this->player('Stranger'))->get('/events')->viewData('page')['props'];

        $this->assertSame(0, $props['hostedTotal']);
        $this->assertSame(0, $props['playingTotal']);
    }

    /**
     * The profile list was built from PlayerBoard rows and rendered
     * `board.title` — a column the event/board split removed — so every row
     * showed a blank name and linked to /events/{board id}, which is not the
     * event id for anything created since that split.
     */
    #[Test]
    public function the_profile_lists_events_with_a_title_and_a_working_link(): void
    {
        Http::fake();

        $event = $this->race();
        $user = $this->player();

        $this->actingAs($user)->post("/events/{$event->id}/enter")->assertRedirect();

        $events = $this->actingAs($user)->get('/settings/profile')->viewData('page')['props']['events'];

        $this->assertCount(1, $events);
        $this->assertSame('Skill of the Month — Mining', $events[0]['title']);
        $this->assertSame($event->id, $events[0]['id']);

        // A race has a rank, not a position on a grid — no invented percentage.
        $this->assertNull($events[0]['progress']);
    }

    /**
     * Each dashboard row links to its own view of /my-events, so those
     * filters have to actually filter — a "view all" that lands on
     * everything is the same dead end as no link.
     */
    #[Test]
    public function the_my_events_filters_show_the_row_they_were_reached_from(): void
    {
        Http::fake();

        $user = $this->player();

        $hosted = $this->race(['title' => 'One I run']);
        BoardAuthor::create(['event_id' => $hosted->id, 'user_id' => $user->id, 'is_owner' => true]);

        $entered = $this->race(['title' => 'One I am in']);
        $this->actingAs($user)->post("/events/{$entered->id}/enter")->assertRedirect();

        $all = $this->actingAs($user)->get('/my-events')->viewData('page')['props'];
        $this->assertCount(2, $all['boards']);
        $this->assertSame(2, $all['counts']['all']);

        $onlyHosted = $this->actingAs($user)->get('/my-events?filter=hosted')->viewData('page')['props'];
        $this->assertCount(1, $onlyHosted['boards']);
        $this->assertSame('One I run', $onlyHosted['boards'][0]['board']['title']);

        $onlyPlaying = $this->actingAs($user)->get('/my-events?filter=playing')->viewData('page')['props'];
        $this->assertCount(1, $onlyPlaying['boards']);
        $this->assertSame('One I am in', $onlyPlaying['boards'][0]['board']['title']);
    }

    /** An unknown filter falls back to everything rather than to nothing. */
    #[Test]
    public function an_unrecognised_filter_shows_everything(): void
    {
        $user = $this->player();
        $event = $this->race();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => true]);

        $props = $this->actingAs($user)->get('/my-events?filter=nonsense')->viewData('page')['props'];

        $this->assertSame('all', $props['filter']);
        $this->assertCount(1, $props['boards']);
    }

    /**
     * An event you host but do not play has neither a progress bar nor a
     * rank. The page used to reach straight into entry.standing.rank for
     * anything that was not a board.
     */
    #[Test]
    public function a_hosted_event_you_do_not_play_carries_no_progress_or_standing(): void
    {
        $user = $this->player();
        $event = $this->race();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => true]);

        $row = $this->actingAs($user)->get('/my-events')->viewData('page')['props']['boards'][0];

        $this->assertNull($row['progress']);
        $this->assertNull($row['standing']);
        $this->assertTrue($row['isHost']);
    }

    // --------------------------------------------------------- admin grant

    /**
     * The first admin on a deployed environment cannot come from the UI
     * without that being a privilege-escalation hole, and AdminUserSeeder
     * invents a local-only account. So it is a console command, and it has
     * to refuse rather than guess when a name is ambiguous.
     */
    #[Test]
    public function the_admin_command_promotes_an_existing_account(): void
    {
        $user = User::factory()->create(['discord_username' => 'Pondake', 'osrs_username' => 'Pondake']);

        $this->artisan('user:make-admin', ['identifier' => 'Pondake'])->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());
    }

    #[Test]
    public function the_admin_command_refuses_a_name_it_cannot_pin_to_one_account(): void
    {
        // Two accounts, one string: a discord handle on one, the OSRS name on
        // the other. Promoting the wrong person is the one outcome worth
        // failing over.
        User::factory()->create(['discord_username' => 'Twin', 'osrs_username' => 'AccountOne']);
        User::factory()->create(['discord_username' => 'Other', 'osrs_username' => 'Twin']);

        $this->artisan('user:make-admin', ['identifier' => 'Twin'])->assertFailed();

        $this->assertSame(0, User::query()->get()->filter(fn (User $u) => $u->isAdmin())->count());
    }

    #[Test]
    public function the_admin_command_fails_on_an_account_that_does_not_exist(): void
    {
        $this->artisan('user:make-admin', ['identifier' => 'NobodyHere'])->assertFailed();
    }

    // ------------------------------------------------------- edit access

    /**
     * Reported as "I am the owner, why can I not edit this like snakes and
     * ladders" — the race page was handed canEdit and rendered nothing for
     * it. Covered on the props here; EventEditFlowTest covers both pages.
     */
    #[Test]
    public function an_admin_can_edit_an_event_they_did_not_create(): void
    {
        $owner = $this->player('Owner');
        $event = $this->race();
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $admin = $this->player('TheAdmin');
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->assertTrue(
            $this->actingAs($admin)->get("/events/{$event->id}")->viewData('page')['props']['canEdit'],
        );
    }

    /** EDITOR grants board creation; it granted nothing at all before. */
    #[Test]
    public function an_editor_can_reach_the_create_endpoint_and_a_player_cannot(): void
    {
        Http::fake();

        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));

        $editor = $this->player('Editor');
        $editor->assignRole($role);

        $this->actingAs($editor)->post('/events', [
            'title' => 'Made by an editor',
            'type' => 'SNAKES_LADDERS',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'size' => 'SIZE_5X5',
        ])->assertRedirect();

        $this->assertSame(1, Event::where('title', 'Made by an editor')->count());

        $player = $this->player('JustAPlayer');
        $player->assignRole(Role::findOrCreate('PLAYER', 'web'));

        $this->actingAs($player)->post('/events', [
            'title' => 'Made by a player',
            'type' => 'SNAKES_LADDERS',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'size' => 'SIZE_5X5',
        ])->assertForbidden();
    }

    // ------------------------------------------- Discord OAuth, gone wrong

    /**
     * Reported from a phone: closing Discord mid-login produced a full
     * Laravel stack trace, headed "GuzzleHttp ClientException — POST
     * /oauth2/token resulted in a 400". Nothing was wrong; a user changed
     * their mind, and the app answered with a 500.
     *
     * The cancel path does not even reach Guzzle — Discord sends the user
     * back with ?error=access_denied and no code, and the old callback fed
     * that straight into the token exchange.
     */
    #[Test]
    public function cancelling_the_discord_login_says_so_instead_of_erroring(): void
    {
        $this->get('/auth/discord/callback?error=access_denied&error_description=denied')
            ->assertRedirect('/login')
            ->assertSessionHas('board-save-error');
    }

    /**
     * The other half, and the one in the screenshot: a callback that does
     * reach Discord and is refused — a spent code, an expired one, a session
     * whose state no longer matches. Socialite throws; nothing caught it.
     */
    #[Test]
    public function a_dead_discord_callback_lands_on_the_login_page_not_a_stack_trace(): void
    {
        config()->set('services.discord', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/discord/callback',
        ]);

        // No state in the session and none on the request: Socialite raises
        // InvalidStateException before it ever calls out, which is the same
        // class of failure as the 400 and takes the same path out.
        $this->withoutExceptionHandling([AuthenticationException::class])
            ->get('/auth/discord/callback?code=spent-code&state=mismatched')
            ->assertRedirect('/login')
            ->assertSessionHas('board-save-error');
    }

    /**
     * A failed link attempt belongs back in account settings, not on the
     * login page — the user is signed in the whole time.
     */
    #[Test]
    public function a_failed_discord_link_returns_to_account_settings(): void
    {
        $this->actingAs($this->player())
            ->withSession(['discord_link_user_id' => $this->player('Linker')->id])
            ->get('/auth/discord/callback?error=access_denied')
            ->assertRedirect('/settings/account');
    }

    // ------------------------------------------------ teams while creating

    /**
     * The Teams tab of the create-event form said "save the board first" and
     * nothing else, which is the report: you turn on team mode, go to the
     * tab it just revealed, and it refuses to do the thing it is for. There
     * is no reason for it to — the teams already exist.
     */
    #[Test]
    public function teams_can_be_assigned_while_the_event_is_still_being_created(): void
    {
        Http::fake();

        $creator = $this->player('Host');
        $creator->assignRole($this->boardCreatorRole());

        $team = Team::create(['name' => 'Zulrah Enjoyers']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $creator->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($creator)->post('/events', [
            'title' => 'Team event',
            'type' => 'SNAKES_LADDERS',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'size' => 'SIZE_5X5',
            'mode' => 'TEAM',
            'access_mode' => 'OPEN',
            'team_ids' => [$team->id],
        ])->assertRedirect();

        $event = Event::where('title', 'Team event')->firstOrFail();

        $this->assertSame(1, BoardTeam::where('event_id', $event->id)->where('team_id', $team->id)->count());
    }

    /** A solo event carrying team rows would be rows nothing ever reads. */
    #[Test]
    public function a_solo_event_ignores_submitted_teams(): void
    {
        Http::fake();

        $creator = $this->player('Host');
        $creator->assignRole($this->boardCreatorRole());

        $team = Team::create(['name' => 'Zulrah Enjoyers']);

        $this->actingAs($creator)->post('/events', [
            'title' => 'Solo event',
            'type' => 'SNAKES_LADDERS',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'size' => 'SIZE_5X5',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'team_ids' => [$team->id],
        ])->assertRedirect();

        $event = Event::where('title', 'Solo event')->firstOrFail();

        $this->assertSame(0, BoardTeam::where('event_id', $event->id)->count());
    }

    /**
     * The list that tab is filled from before an event id exists.
     *
     * Scoped to teams the creator can actually see — a member here, since
     * this one has no Discord server. Offering every team on the site was
     * the bug this replaced; see TeamOwnershipTest for the visibility rule
     * itself.
     */
    #[Test]
    public function the_team_picker_has_a_list_to_offer_before_the_event_exists(): void
    {
        $user = $this->player();
        $team = Team::create(['name' => 'Zulrah Enjoyers']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => TeamMember::OWNER]);

        $this->actingAs($user)
            ->getJson('/teams/options')
            ->assertOk()
            ->assertJsonPath('teams.0.name', 'Zulrah Enjoyers');
    }

    // ---------------------------------------------------- event blueprints

    #[Test]
    public function the_title_autocomplete_offers_active_blueprints_only(): void
    {
        $creator = $this->player('Host');
        $creator->assignRole($this->boardCreatorRole());

        EventBlueprint::create(['title' => 'Skill of the Week', 'type' => 'SKILL_RACE', 'metric' => 'overall']);
        EventBlueprint::create(['title' => 'Retired Format', 'is_active' => false]);

        $response = $this->actingAs($creator)->getJson('/event-blueprints')->assertOk();

        $titles = collect($response->json('blueprints'))->pluck('title');

        $this->assertContains('Skill of the Week', $titles);
        $this->assertNotContains('Retired Format', $titles);
    }

    /**
     * A blueprint prefills type and metric, so a blueprint carrying a pair
     * the create form rejects would look like the blueprint broke the form.
     */
    #[Test]
    public function a_blueprint_cannot_be_saved_with_a_metric_the_event_form_would_reject(): void
    {
        $creator = $this->player('Host');
        $creator->assignRole($this->boardCreatorRole());

        $this->actingAs($creator)->post('/admin/blueprints', [
            'title' => 'Nonsense',
            'type' => 'SKILL_RACE',
            'metric' => 'not-a-real-metric',
        ])->assertSessionHasErrors('metric');
    }

    #[Test]
    public function managing_blueprints_needs_the_board_creation_permission(): void
    {
        $player = $this->player('JustAPlayer');
        $player->assignRole(Role::findOrCreate('PLAYER', 'web'));

        $this->actingAs($player)->get('/admin/blueprints')->assertForbidden();
        $this->actingAs($player)->getJson('/event-blueprints')->assertForbidden();
    }

    // ------------------------------------- created vs joined on the profile

    /**
     * The profile listed every event in one column with an Owner badge as
     * the only hint which was which. The page now filters on this flag, so
     * it has to be there and it has to mean "you run this one".
     */
    #[Test]
    public function the_profile_marks_which_events_you_run(): void
    {
        $host = $this->player('Host');
        $mine = $this->race(['title' => 'Mine']);
        BoardAuthor::create(['event_id' => $mine->id, 'user_id' => $host->id, 'is_owner' => true]);

        $theirs = $this->race(['title' => 'Theirs']);
        BoardAuthor::create(['event_id' => $theirs->id, 'user_id' => $this->player('Someone')->id, 'is_owner' => true]);
        EventStanding::create([
            'event_id' => $theirs->id,
            'user_id' => $host->id,
            'username' => 'Host',
            'start_value' => 0,
        ]);

        $events = collect($this->actingAs($host)->get('/settings/profile')->viewData('page')['props']['events'])
            ->keyBy('title');

        $this->assertTrue($events['Mine']['isHost']);
        $this->assertFalse($events['Theirs']['isHost']);
    }

    // ------------------------------------------------------------- dates

    /**
     * An event without a window is not an event: every status badge, the
     * standings range and the bingo cutoff all key off one, and each null
     * date makes some other piece of code invent a fallback.
     */
    #[Test]
    public function creating_an_event_without_dates_is_refused(): void
    {
        Http::fake();

        $creator = $this->player('Host');
        $creator->assignRole($this->boardCreatorRole());

        $this->actingAs($creator)->post('/events', [
            'title' => 'No window',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
        ])->assertSessionHasErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function an_end_date_before_the_start_is_refused(): void
    {
        Http::fake();

        $creator = $this->player('Host');
        $creator->assignRole($this->boardCreatorRole());

        $this->actingAs($creator)->post('/events', [
            'title' => 'Backwards',
            'type' => 'SNAKES_LADDERS',
            'size' => 'SIZE_5X5',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-01',
        ])->assertSessionHasErrors('end_date');
    }

    /**
     * Editing keeps them nullable — events created before the rule exist
     * with null dates, and a required field would make those uneditable —
     * but a start with no end is the one combination that reads as a
     * mistake rather than as "no dates set".
     */
    #[Test]
    public function editing_refuses_a_start_date_with_no_end_date(): void
    {
        $owner = $this->player('Owner');
        $event = $this->race(['start_date' => null, 'end_date' => null]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $this->actingAs($owner)
            ->patch("/events/{$event->id}", ['start_date' => '2026-09-01', 'end_date' => null])
            ->assertSessionHasErrors('end_date');
    }

    #[Test]
    public function editing_still_accepts_an_event_with_no_dates_at_all(): void
    {
        $owner = $this->player('Owner');
        $event = $this->race(['start_date' => null, 'end_date' => null]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $this->actingAs($owner)
            ->patch("/events/{$event->id}", ['title' => 'Renamed', 'start_date' => null, 'end_date' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $event->fresh()->title);
    }

    // -------------------------------------------- bingo settings, one form

    /**
     * A bingo event's win condition is as much "the event's settings" as its
     * title is. Sending people to a second place for it is what made bingo
     * feel half-finished, so the event settings modal writes both — through
     * the same service the card's own endpoint uses.
     */
    #[Test]
    public function the_event_settings_form_can_change_the_bingo_card(): void
    {
        $owner = $this->player('Owner');

        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $card = $event->bingoCard()->create(['size' => 3, 'win_condition' => 'LINE']);
        app(BingoService::class)->ensureSquares($card);

        $this->actingAs($owner)->patch("/events/{$event->id}", [
            'bingo_size' => 5,
            'win_condition' => 'FULL_HOUSE',
            'line_bonus' => 10,
            'requires_approval' => false,
        ])->assertSessionHasNoErrors();

        $card = $card->fresh();

        $this->assertSame(5, $card->size);
        $this->assertSame('FULL_HOUSE', $card->win_condition);
        $this->assertSame(10, $card->line_bonus);
        $this->assertFalse($card->requires_approval);
        // Grown, not just relabelled — the squares have to follow the size.
        $this->assertSame(25, $card->squares()->count());
    }

    /** Deleting somebody's progress is not something a size dropdown does. */
    #[Test]
    public function shrinking_a_card_with_progress_on_it_is_refused_through_the_settings_form(): void
    {
        $owner = $this->player('Owner');

        $event = Event::create([
            'title' => 'Card night',
            'type' => 'BINGO',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $card = $event->bingoCard()->create(['size' => 5, 'win_condition' => 'LINE']);
        app(BingoService::class)->ensureSquares($card);

        // A completion on a square that a 3x3 card would not have.
        BingoCompletion::create([
            'bingo_square_id' => $card->squares()->where('position', 20)->value('id'),
            'user_id' => $owner->id,
            'marked_by' => $owner->id,
            'status' => 'APPROVED',
        ]);

        $this->actingAs($owner)
            ->patch("/events/{$event->id}", ['bingo_size' => 3])
            ->assertSessionHasErrors('bingo_size');

        $this->assertSame(5, $card->fresh()->size);
    }

    // ------------------------------------------------- editing a non-bingo

    /**
     * Reported as "I hit save on a board, it jumped back to Basics and saved
     * nothing". Two faults in one request, and this is the one that broke
     * every non-bingo event:
     *
     * The settings form carries every type's fields at once, so editing a
     * Snakes & Ladders event posted bingo_size: null — present, therefore
     * validated by `sometimes|integer`, therefore rejected with "The card
     * size field must be an integer" about a card the event does not have
     * and the form does not show.
     *
     * Asserted at the endpoint rather than through the form, because the
     * rule that has to hold is "a null for another type's field does not
     * sink the save".
     */
    #[Test]
    public function editing_a_snakes_and_ladders_event_is_not_sunk_by_bingo_fields(): void
    {
        $owner = $this->player('Owner');

        $event = Event::create([
            'title' => 'Board night',
            'type' => 'SNAKES_LADDERS',
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'is_listed' => true,
        ]);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $this->actingAs($owner)
            ->patch("/events/{$event->id}", ['title' => 'Renamed', 'bingo_size' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $event->fresh()->title);
    }

    /**
     * A GUILD event whose server was never set — legal until the form
     * started requiring one — locked itself away from everybody, including
     * the admin who has to go in and fix it. canEditEvent() let an admin
     * edit any event while hasAccess() would not let them open one, so the
     * only route to the settings was behind a gate that turned them away.
     */
    #[Test]
    public function an_admin_can_open_a_guild_event_with_no_server_set(): void
    {
        $event = $this->race(['access_mode' => 'GUILD', 'required_guild_id' => null, 'type' => 'SNAKES_LADDERS', 'metric' => null]);
        $event->board()->create(['size' => 'SIZE_5X5']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $this->player('Owner')->id, 'is_owner' => true]);

        $admin = $this->player('TheAdmin');
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $page = $this->actingAs($admin)->get("/events/{$event->id}")->viewData('page');

        // The gate would have rendered Boards/AccessGate instead.
        $this->assertNotSame('Boards/AccessGate', $page['component']);
    }

    /** And the wording no longer calls a Discord server a "guild id". */
    #[Test]
    public function the_missing_server_message_talks_about_servers_not_guilds(): void
    {
        $owner = $this->player('Owner');
        $event = $this->race(['access_mode' => 'OPEN']);
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $this->actingAs($owner)
            ->patch("/events/{$event->id}", ['access_mode' => 'GUILD'])
            ->assertSessionHasErrors('required_guild_id');

        $message = $this->app['session.store']->get('errors')->get('required_guild_id')[0];

        $this->assertStringNotContainsStringIgnoringCase('guild', $message);
        $this->assertStringContainsStringIgnoringCase('server', $message);
    }

    /** EDITOR is the role that carries canCreateBoards in these tests. */
    private function boardCreatorRole(): Role
    {
        $role = Role::findOrCreate('EDITOR', 'web');
        $role->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));

        return $role;
    }
}
