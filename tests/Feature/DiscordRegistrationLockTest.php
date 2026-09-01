<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSiteUnlocked;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The other way to get an account.
 *
 * Registration is closed while the site is locked, but Discord is two things
 * at once: a way to sign in, and a way to acquire an account without ever
 * seeing a registration form. The OAuth routes have to stay open — an admin
 * signs in through them — so the account-creating half is refused separately,
 * inside the callback.
 *
 * Worth its own file because it is the kind of hole that looks closed from
 * the route list: `/register` redirects to the lock screen and everything
 * reads as shut, while `auth/discord/callback` quietly makes users.
 */
class DiscordRegistrationLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.discord', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/discord/callback',
        ]);
    }

    private function lock(): void
    {
        Setting::setMany(['site_lock_enabled' => true, 'site_lock_password' => 'irrelevant-here']);
    }

    /** Stands in for the round trip Discord would have answered. */
    private function discordAnswers(string $discordId, string $username = 'newcomer'): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw(['global_name' => 'Newcomer'])->map([
            'id' => $discordId,
            'nickname' => $username,
            'name' => $username,
            'avatar' => null,
            'token' => 'test-token',
        ]);

        // The raw payload is read directly for global_name, which map() does
        // not carry across.
        $socialiteUser->user = ['global_name' => 'Newcomer'];

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * The beta case, added 2026-08-30.
     *
     * Somebody who typed the shared password is not a stranger any more —
     * the door already let them in, and they could already register through
     * the email form, which stops at `registration_open` and nothing else.
     * Refusing them at "Continue with Discord" gave one person two different
     * answers depending on which button they pressed, and Discord is the
     * button a clan actually uses.
     */
    #[Test]
    public function the_shared_password_lets_a_newcomer_in_through_discord(): void
    {
        $this->lock();
        $this->discordAnswers('123123123');

        // What SiteLockController writes once the password matches. Set
        // directly rather than by POSTing the form: this test is about what
        // the callback does with the flag, and SiteLockTest already proves
        // the flag gets written.
        $this->withSession([EnsureSiteUnlocked::SESSION_KEY => true]);

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect();

        $this->assertNotNull(User::where('discord_id', '123123123')->first());
        $this->assertAuthenticated();
    }

    /**
     * Full lockdown is not softened by anything, the shared password
     * included — "nothing but an admin session gets through" has to stay
     * literally true, or the stricter switch is weaker than the looser one.
     */
    #[Test]
    public function full_lockdown_refuses_discord_even_with_the_unlock_flag(): void
    {
        Setting::setMany(['admin_lockdown_enabled' => true]);
        $this->discordAnswers('321321321');

        $this->withSession([EnsureSiteUnlocked::SESSION_KEY => true]);

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect('/login');

        $this->assertNull(User::where('discord_id', '321321321')->first());
        $this->assertGuest();
    }

    /** An admin's explicit "no" outranks the door password. */
    #[Test]
    public function a_closed_registration_switch_outranks_the_unlock_flag(): void
    {
        $this->lock();
        Setting::setMany(['registration_open' => false]);
        $this->discordAnswers('456456456');

        $this->withSession([EnsureSiteUnlocked::SESSION_KEY => true]);

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect('/login');

        $this->assertNull(User::where('discord_id', '456456456')->first());
        $this->assertGuest();
    }

    #[Test]
    public function a_stranger_cannot_make_an_account_through_discord_while_locked(): void
    {
        $this->lock();
        $this->discordAnswers('999888777');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect('/login');

        $this->assertNull(User::where('discord_id', '999888777')->first());
        $this->assertGuest();
    }

    /**
     * The person building the site signs in through this same route, so an
     * account that already exists has to keep working.
     */
    #[Test]
    public function an_account_that_already_exists_still_signs_in(): void
    {
        $this->lock();

        $existing = User::factory()->create([
            'osrs_username' => 'Pondake',
            'discord_id' => '111222333',
        ]);

        $this->discordAnswers('111222333', 'pondake');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect();

        $this->assertSame($existing->id, Auth::id());
    }

    /**
     * The other reason to be shut. `registration_open` is an admin switch
     * that has always gated the email/password form; it gated nothing else,
     * so turning it off closed the front door and left this one open.
     */
    #[Test]
    public function closing_registration_closes_the_discord_route_too(): void
    {
        Setting::set('registration_open', false);
        $this->discordAnswers('777666555');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect('/login');

        $this->assertNull(User::where('discord_id', '777666555')->first());
    }

    #[Test]
    public function an_open_site_still_makes_accounts_as_normal(): void
    {
        $this->discordAnswers('444555666');

        $this->get('/auth/discord/callback?code=whatever')->assertRedirect();

        $this->assertNotNull(User::where('discord_id', '444555666')->first());
    }
}
