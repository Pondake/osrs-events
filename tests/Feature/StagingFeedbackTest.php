<?php

namespace Tests\Feature;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\EventStanding;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGuild;
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

        app(\App\Services\EventStandingsService::class)
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

        $this->assertSame(1, $props['mineTotal']);
        $this->assertSame($event->id, $props['mine'][0]['id']);
    }

    /** An event you run is yours whether or not you also play in it. */
    #[Test]
    public function an_event_you_created_is_yours_even_without_taking_part(): void
    {
        $event = $this->race();
        $owner = $this->player('Owner');

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $props = $this->actingAs($owner)->get('/events')->viewData('page')['props'];

        $this->assertSame($event->id, $props['mine'][0]['id'] ?? null);
        $this->assertSame(0, EventStanding::count(), 'the host was not entered as a participant');
    }

    #[Test]
    public function someone_elses_event_is_not_listed_as_yours(): void
    {
        $event = $this->race();
        $owner = $this->player('Owner');
        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $owner->id, 'is_owner' => true]);

        $props = $this->actingAs($this->player('Stranger'))->get('/events')->viewData('page')['props'];

        $this->assertSame(0, $props['mineTotal']);
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
            'mode' => 'SOLO',
            'access_mode' => 'OPEN',
            'size' => 'SIZE_5X5',
        ])->assertForbidden();
    }
}
