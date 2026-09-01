<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared password in front of a pre-launch site.
 *
 * The rules worth pinning down are the ones that make it either useless or
 * unusable if they break: the door has to open, exactly two things stay
 * reachable while it is shut, an admin never needs the shared password, and
 * the password itself never comes back out of the server.
 */
class SiteLockTest extends TestCase
{
    use RefreshDatabase;

    private function lock(string $password = 'clan-secret'): void
    {
        Setting::setMany([
            'site_lock_enabled' => true,
            'site_lock_password' => Hash::make($password),
        ]);
    }

    #[Test]
    public function an_unlocked_site_is_untouched(): void
    {
        $this->get('/')->assertOk();
    }

    #[Test]
    public function a_locked_site_sends_a_visitor_to_the_app_lock_screen(): void
    {
        $this->lock();

        // `/events` and not `/teams`: Laravel sorts the middleware stack by
        // its own priority list and `auth` outranks anything appended to the
        // web group, so an auth-gated route answers 302-to-login before this
        // middleware ever runs. Both answers say "not for you", but only the
        // public-but-app route proves THIS one.
        $this->get('/events')->assertRedirect('/locked');
    }

    /**
     * A stale tab is sent away by the browser, not by Inertia.
     *
     * Reported from staging 2026-08-31: somebody had the site open before the
     * lock went on, so their client still held unlocked props — full nav, user
     * menu, the lot. Inertia leaves the current page on screen until a visit
     * resolves, so their next click showed the whole signed-in site for the
     * length of the request before the lock screen replaced it.
     *
     * 409 + X-Inertia-Location is what makes the client do a real navigation
     * and throw that stale page away. An ordinary request still gets a 302 —
     * the test above covers that half.
     */
    #[Test]
    public function an_inertia_visit_to_a_locked_route_forces_a_full_navigation(): void
    {
        $this->lock();

        // The version header matters: without it Inertia sees an asset
        // mismatch and answers with its OWN 409 pointing back at the
        // requested URL, which would make this pass for the wrong reason.
        // (Worth knowing on its own — after a deploy that check already
        // forces the hard navigation. The gap this closes is a lock toggled
        // WITHOUT a deploy, which is exactly how it was reported.)
        $version = app(\App\Http\Middleware\HandleInertiaRequests::class)->version(request());

        $this->get('/events', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', url('/locked'));
    }

    #[Test]
    public function the_lock_screen_and_the_login_page_stay_reachable(): void
    {
        $this->lock();

        $this->get('/locked')->assertOk();
        $this->get('/login')->assertOk();
    }

    /**
     * The lock keeps the APP unannounced. It is not meant to hide the shop
     * window: the landing pages and the guides are what a search engine
     * indexes and what somebody lands on from a Discord post, and serving
     * those a password box costs the launch the audience it is being built
     * for.
     */
    #[Test]
    public function the_public_pages_stay_open_while_the_app_is_locked(): void
    {
        $this->lock();

        $this->get('/')->assertOk();
        $this->get('/osrs-snakes-and-ladders')->assertOk();
        $this->get('/osrs-clan-events')->assertOk();
        $this->get('/osrs-event-ideas')->assertOk();
        $this->get('/osrs-bingo')->assertOk();
        $this->get('/osrs-skill-race')->assertOk();
        $this->get('/osrs-drop-race')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
    }

    /** Including the CMS pages, which is where About and Privacy live. */
    #[Test]
    public function a_published_cms_page_stays_open_too(): void
    {
        $this->lock();

        Page::create([
            'slug' => 'about',
            'title' => 'About us',
            'is_published' => true,
            'blocks' => [['type' => 'paragraph', 'text' => 'We run clan events.']],
        ]);

        $this->get('/about')->assertOk();
    }

    /**
     * The CMS route is the catch-all `/{page}`, which matches any single
     * segment — so letting it through by PATH would have unlocked half the
     * app with it. It is allowed by route name instead, and this is the test
     * that says why.
     */
    #[Test]
    public function letting_the_cms_through_does_not_let_the_app_through(): void
    {
        $this->lock();

        $this->get('/events')->assertRedirect('/locked');
    }

    // ------------------------------------------------- no new accounts

    /**
     * A shut door that hands out keys is not shut.
     *
     * Sign-in stays open, because whoever is building the site has to get in.
     * Registration does not: an account acquired now is an account that can
     * read whatever the lock is there to keep unannounced.
     */
    #[Test]
    public function registration_is_closed_while_the_site_is_locked(): void
    {
        $this->lock();

        $this->get('/register')->assertRedirect('/locked');

        $this->post('/register', [
            'name' => 'Newcomer',
            'email' => 'new@example.com',
            'password' => 'Correct-horse-1',
            'password_confirmation' => 'Correct-horse-1',
        ])->assertRedirect('/locked');

        $this->assertNull(User::where('email', 'new@example.com')->first());
    }

    /**
     * The beta question, asked 2026-08-30: does typing the shared password
     * let a stranger make an account?
     *
     * For the email form, yes — and that is worth pinning rather than
     * discovering. `isShutFor()` returns false once the session flag is set,
     * so the middleware stops intercepting anything, and
     * `RegisteredUserController` only ever checked `registration_open`. The
     * password IS the invitation here, which is exactly what a closed beta
     * wants. Discord signup is the half that does NOT work this way — see
     * DiscordRegistrationLockTest.
     */
    #[Test]
    public function the_shared_password_lets_a_newcomer_register(): void
    {
        $this->lock();

        $this->post('/locked', ['password' => 'clan-secret'])->assertRedirect();

        $this->get('/register')->assertOk();

        $this->post('/register', [
            'nickname' => 'Newcomer',
            'osrs_username' => 'BetaTester',
            'email' => 'beta@example.com',
            'password' => 'Correct-horse-1',
            'password_confirmation' => 'Correct-horse-1',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(User::where('email', 'beta@example.com')->first());
    }

    /** The admin switch still outranks the door — an explicit no is a no. */
    #[Test]
    public function the_shared_password_does_not_beat_a_closed_registration_switch(): void
    {
        $this->lock();
        Setting::setMany(['registration_open' => false]);

        $this->post('/locked', ['password' => 'clan-secret'])->assertRedirect();

        $this->post('/register', [
            'nickname' => 'Newcomer',
            'osrs_username' => 'BetaTester',
            'email' => 'nope@example.com',
            'password' => 'Correct-horse-1',
            'password_confirmation' => 'Correct-horse-1',
        ])->assertForbidden();

        $this->assertNull(User::where('email', 'nope@example.com')->first());
    }

    #[Test]
    public function signing_in_stays_open(): void
    {
        $this->lock();

        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
    }

    /**
     * The whole reset, not just the pages it starts on.
     *
     * The allow-list had `reset-password/*` — the link from the email, which
     * carries a token — and not `reset-password`, which is where that page
     * POSTs to. So a locked site let somebody open the link, type a new
     * password and then answered 423 when they saved it. The recovery path
     * was dead in exactly the state that needs it most, and nothing caught it
     * because every test stopped at the GET.
     */
    #[Test]
    public function a_password_can_actually_be_reset_while_the_site_is_locked(): void
    {
        Notification::fake();
        $this->lock();

        $user = User::factory()->create([
            'osrs_username' => 'LockedOut',
            'email' => 'lockedout@example.com',
            'password' => Hash::make('Original-pass-1'),
        ]);

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Brand-new-pass-9',
            'password_confirmation' => 'Brand-new-pass-9',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Brand-new-pass-9', $user->fresh()->password));
    }

    #[Test]
    public function registration_reopens_once_the_lock_comes_off(): void
    {
        $this->get('/register')->assertOk();
    }

    /**
     * What a locked visitor is OFFERED is decided in the browser, from the
     * props below — the header builds its nav from `auth.user` and
     * `site.locked`. This suite runs with Inertia SSR off (see phpunit.xml),
     * so it can assert the ingredients and not the rendered menu; the menu
     * itself is covered by tests/js/pageState.test.js and was checked in a
     * browser against interleaved signed-in and signed-out requests.
     */
    #[Test]
    public function a_locked_visitor_gets_props_that_say_no_session_and_a_shut_door(): void
    {
        $this->lock();

        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        // Interleaved on purpose: the bug this guards against only showed
        // when a request that SHOULD carry app links was served immediately
        // before one that should not.
        $this->actingAs($admin)->get('/')->assertOk();

        // actingAs() stays in force for the rest of the test, so the second
        // visitor has to be made a stranger explicitly.
        Auth::logout();
        $this->flushSession();

        $props = $this->get('/')->assertOk()->viewData('page')['props'];

        $this->assertNull($props['auth']['user']);
        $this->assertTrue($props['site']['locked']);
    }

    /**
     * The header keys its trimmed nav off this prop, so it has to say the
     * right thing on the pages a locked site still serves — otherwise a
     * visitor gets a full menu of links that all bounce back to the door.
     */
    #[Test]
    public function a_public_page_still_tells_the_client_the_door_is_shut(): void
    {
        $this->lock();

        $site = $this->get('/')->viewData('page')['props']['site'];

        $this->assertTrue($site['locked']);
    }

    /** And says so no longer, once the visitor has the password. */
    #[Test]
    public function typing_the_password_opens_the_nav_as_well_as_the_app(): void
    {
        $this->lock('clan-secret');

        $this->post('/locked', ['password' => 'clan-secret']);

        $this->assertFalse($this->get('/')->viewData('page')['props']['site']['locked']);
    }

    /** An unpublished page is still a 404, lock or no lock. */
    #[Test]
    public function an_unpublished_page_is_not_a_way_in(): void
    {
        $this->lock();

        Page::create(['slug' => 'draft', 'title' => 'Draft', 'is_published' => false, 'blocks' => []]);

        $this->get('/draft')->assertNotFound();
    }

    #[Test]
    public function the_right_password_opens_the_door(): void
    {
        $this->lock('clan-secret');

        $this->post('/locked', ['password' => 'clan-secret'])->assertRedirect();

        $this->get('/events')->assertOk();
    }

    #[Test]
    public function the_wrong_password_does_not(): void
    {
        $this->lock('clan-secret');

        $this->post('/locked', ['password' => 'nope'])->assertSessionHasErrors('password');

        $this->get('/events')->assertRedirect('/locked');
    }

    /** An admin should never need to be told the shared password. */
    #[Test]
    public function an_admin_session_walks_straight_through(): void
    {
        $this->lock();

        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->get('/events')->assertOk();
    }

    /**
     * The pre-launch door is for strangers, not for people who already have
     * a way in. This used to be backwards — every signed-in non-admin met
     * the door same as an anonymous visitor, reported live: a logged-in
     * player saw the lock screen's banner AND the app's own onboarding modal
     * at once, because the header/home page and the route middleware
     * disagreed about whether this visitor had access at all.
     */
    #[Test]
    public function an_ordinary_signed_in_account_walks_straight_through(): void
    {
        $this->lock();

        $player = User::factory()->create(['osrs_username' => 'Pondake']);
        $player->assignRole(Role::findOrCreate('PLAYER', 'web'));

        $this->actingAs($player)->get('/events')->assertOk();
    }

    /** Matches the route-level fix above: the shared `locked` prop must agree. */
    #[Test]
    public function an_ordinary_signed_in_account_gets_props_that_say_the_door_is_open(): void
    {
        $this->lock();

        $player = User::factory()->create(['osrs_username' => 'Pondake']);
        $player->assignRole(Role::findOrCreate('PLAYER', 'web'));

        $props = $this->actingAs($player)->get('/')->viewData('page')['props'];

        $this->assertFalse($props['site']['locked']);
    }

    /** An anonymous stranger is still the one the door is actually for. */
    #[Test]
    public function a_stranger_still_meets_the_door(): void
    {
        $this->lock();

        $this->get('/events')->assertRedirect('/locked');
    }

    // ------------------------------------------------------- full lockdown

    private function lockdown(): void
    {
        Setting::set('admin_lockdown_enabled', true);
    }

    /** The stricter switch refuses even a signed-in ordinary account. */
    #[Test]
    public function full_lockdown_refuses_an_ordinary_signed_in_account(): void
    {
        $this->lockdown();

        $player = User::factory()->create(['osrs_username' => 'Pondake']);
        $player->assignRole(Role::findOrCreate('PLAYER', 'web'));

        $this->actingAs($player)->get('/events')->assertRedirect('/locked');
    }

    /** Unlike the pre-launch door, full lockdown does not spare the public pages. */
    #[Test]
    public function full_lockdown_refuses_the_public_pages_too(): void
    {
        $this->lockdown();

        $this->get('/')->assertRedirect('/locked');
        $this->get('/osrs-snakes-and-ladders')->assertRedirect('/locked');
    }

    /** An admin is still the one way through. */
    #[Test]
    public function full_lockdown_still_lets_an_admin_in(): void
    {
        $this->lockdown();

        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->get('/events')->assertOk();
    }

    /** The shared password is exactly what full lockdown is built to refuse. */
    #[Test]
    public function full_lockdown_refuses_the_shared_password_too(): void
    {
        $this->lockdown();
        Setting::set('site_lock_enabled', true);
        Setting::set('site_lock_password', Hash::make('clan-secret'));

        $this->post('/locked', ['password' => 'clan-secret'])->assertSessionHasErrors('password');

        $this->get('/events')->assertRedirect('/locked');
    }

    /** Sign-in itself still has to work, or an admin has no way to prove it. */
    #[Test]
    public function full_lockdown_still_lets_sign_in_pages_through(): void
    {
        $this->lockdown();

        $this->get('/login')->assertOk();
        $this->get('/locked')->assertOk();
    }

    /**
     * Every fetch() in this app sends Accept: application/json. Redirecting
     * those to an HTML lock screen would have them parse a login page as a
     * failed API response, which is a worse failure than a status code.
     *
     * Asserted against `/events`: a route that needs no login but is not a
     * public page. Laravel sorts the combined middleware stack by its own
     * priority list and `auth` outranks anything appended to the web group,
     * so on an auth-gated route the redirect to login lands first and this
     * middleware never runs.
     */
    #[Test]
    public function a_json_caller_gets_a_status_code_not_a_redirect(): void
    {
        $this->lock();

        $this->getJson('/events')->assertStatus(423);
    }

    /**
     * The lock screen offers "Running this site? Log in" as the other way
     * in, and that click used to land on a fully dressed page: nav, user
     * menu, announcement banner. Every one of those links bounces straight
     * back to the lock screen, so it was a menu of dead ends wrapped around
     * a password box — on the one page a stranger is meant to reach.
     *
     * Asserted on the prop rather than the markup, because the prop is what
     * AppRoot decides on and a rendered check would be testing Vue.
     */
    #[Test]
    public function the_login_page_drops_the_site_chrome_while_the_lock_is_on(): void
    {
        $this->lock();

        $this->assertTrue($this->get('/login')->viewData('page')['props']['site']['locked']);
    }

    #[Test]
    public function the_login_page_keeps_its_chrome_on_an_open_site(): void
    {
        $this->assertFalse($this->get('/login')->viewData('page')['props']['site']['locked']);
    }

    /**
     * The stored value is a bcrypt hash and nothing needs it client-side —
     * the form only has to know whether one exists.
     */
    #[Test]
    public function the_admin_page_never_ships_the_password_hash(): void
    {
        $this->lock('clan-secret');

        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $settings = $this->actingAs($admin)->get('/admin/site')->viewData('page')['props']['settings'];

        $this->assertNull($settings['site_lock_password']);
        $this->assertTrue($settings['site_lock_has_password']);
    }

    // ------------------------------------------------ the public announcement

    /**
     * The default, and the reason the switch exists at all: an announcement
     * is normally written for the people already using the site ("summer
     * bingo starts Friday, sign up in #events"), and the lock screen is the
     * one page a stranger can reach.
     *
     * Withheld, not hidden: the prop must be null, because a value that
     * reaches the browser has already been disclosed whatever the template
     * does with it.
     */
    #[Test]
    public function an_announcement_is_withheld_from_a_locked_stranger_by_default(): void
    {
        $this->lock();
        Setting::setMany(['announcement' => 'Summer bingo starts Friday']);

        $this->get('/locked')->assertInertia(
            fn ($page) => $page->where('site.announcement', null),
        );
    }

    /**
     * Marked public, it is the one thing the door may say — a launch date, a
     * call for beta testers, a Discord link. Without this the door can say
     * nothing at all, which makes announcing the site to strangers
     * pointless: they arrive at a password box with no reason to come back.
     */
    #[Test]
    public function a_public_announcement_reaches_a_locked_stranger(): void
    {
        $this->lock();
        Setting::setMany([
            'announcement' => 'Looking for beta testers — join the Discord',
            'announcement_public' => true,
        ]);

        $this->get('/locked')->assertInertia(
            fn ($page) => $page->where('site.announcement', 'Looking for beta testers — join the Discord'),
        );
    }

    /** Making it public does not make anything ELSE public. */
    #[Test]
    public function a_public_announcement_does_not_open_the_door(): void
    {
        $this->lock();
        Setting::setMany(['announcement' => 'Beta soon', 'announcement_public' => true]);

        $this->get('/events')->assertRedirect('/locked');
    }

    /**
     * Saving any other setting used to be the moment a blank password field
     * would have wiped the lock. Blank means unchanged.
     */
    #[Test]
    public function saving_the_settings_form_without_a_password_keeps_the_existing_one(): void
    {
        $this->lock('clan-secret');
        $before = Setting::get('site_lock_password');

        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        // The payload has to be COMPLETE, and the assertion has to be
        // assertSessionHasNoErrors rather than assertRedirect. This test used
        // to omit three required fields and assert only a redirect — and a
        // validation failure is a redirect too, so it passed without the form
        // ever saving. Found 2026-08-30 by adding a fourth required field and
        // watching a different test fail while this one stayed green.
        $this->actingAs($admin)->put('/admin/site', [
            'registration_open' => true,
            'default_board_size' => 'SIZE_7X7',
            'default_dice_roll_limit' => 1,
            'default_event_duration' => '2w',
            'announcement' => null,
            'announcement_type' => 'info',
            'announcement_public' => false,
            'discord_webhooks_enabled' => false,
            'kofi_url' => 'https://ko-fi.com/pondake',
            'site_lock_enabled' => true,
            'site_lock_password' => '',
            'admin_lockdown_enabled' => false,
        ])->assertSessionHasNoErrors();

        $this->assertSame($before, Setting::get('site_lock_password'));
    }

    /** Turning it on with nothing to check against would lock everyone out. */
    #[Test]
    public function enabling_the_lock_without_a_password_is_refused(): void
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        // Complete but for the password, so the error this asserts is the
        // only one the form could have produced — see the note above.
        $this->actingAs($admin)->put('/admin/site', [
            'registration_open' => true,
            'default_board_size' => 'SIZE_7X7',
            'default_dice_roll_limit' => 1,
            'default_event_duration' => '2w',
            'announcement' => null,
            'announcement_type' => 'info',
            'announcement_public' => false,
            'discord_webhooks_enabled' => false,
            'kofi_url' => 'https://ko-fi.com/pondake',
            'site_lock_enabled' => true,
            'site_lock_password' => '',
            'admin_lockdown_enabled' => false,
        ])->assertSessionHasErrors('site_lock_password');
    }
}
