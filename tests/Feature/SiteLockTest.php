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

    /** Being signed in is not the same as being allowed in. */
    #[Test]
    public function an_ordinary_account_still_meets_the_door(): void
    {
        $this->lock();

        $player = User::factory()->create(['osrs_username' => 'Pondake']);
        $player->assignRole(Role::findOrCreate('PLAYER', 'web'));

        $this->actingAs($player)->get('/events')->assertRedirect('/locked');
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

        $this->actingAs($admin)->put('/admin/site', [
            'registration_open' => true,
            'default_board_size' => 'SIZE_7X7',
            'default_dice_roll_limit' => 1,
            'announcement' => null,
            'announcement_type' => 'info',
            'kofi_url' => 'https://ko-fi.com/pondake',
            'site_lock_enabled' => true,
            'site_lock_password' => '',
        ])->assertRedirect();

        $this->assertSame($before, Setting::get('site_lock_password'));
    }

    /** Turning it on with nothing to check against would lock everyone out. */
    #[Test]
    public function enabling_the_lock_without_a_password_is_refused(): void
    {
        $admin = User::factory()->create(['osrs_username' => 'TheAdmin']);
        $admin->assignRole(Role::findOrCreate('ADMIN', 'web'));

        $this->actingAs($admin)->put('/admin/site', [
            'registration_open' => true,
            'default_board_size' => 'SIZE_7X7',
            'default_dice_roll_limit' => 1,
            'announcement' => null,
            'announcement_type' => 'info',
            'kofi_url' => 'https://ko-fi.com/pondake',
            'site_lock_enabled' => true,
            'site_lock_password' => '',
        ])->assertSessionHasErrors('site_lock_password');
    }
}
